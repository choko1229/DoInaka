<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Enums\AuditAction;
use App\Enums\CommentStatus;
use App\Enums\Recurrence;
use App\Enums\RevisionCause;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Correction;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Media;
use App\Models\Region;
use App\Models\Spot;
use App\Models\Submission;
use App\Models\User;
use App\Models\Visit;
use App\Services\Audit\AuditLogger;
use App\Services\Content\ContentService;
use App\Services\Content\EventLifecycle;
use App\Services\Content\RevisionService;
use App\Services\Crawl\CrawlTrust;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 人の審査(設計書8章): 承認・却下・元に戻す。
 * 承認は1つのトランザクションで、公開テーブルへの書き込み、revisions への記録、画像の付け替え、投稿者の承認数の加算を行う。
 * 状態の変更は SubmissionStateMachine に任せる。
 */
final class ReviewService
{
    public function __construct(
        private readonly SubmissionStateMachine $machine,
        private readonly ContentService $content,
        private readonly RevisionService $revisions,
        private readonly AuditLogger $audit,
        private readonly CrawlTrust $trust,
        private readonly EventLifecycle $lifecycle,
    ) {}

    /** $actor が null のときは、AI の自動承認(履歴には人の名前が付かない) */
    public function approve(Submission $submission, ?User $actor): Submission
    {
        // 状態の確認を先にする(許されない遷移なら、公開テーブルに何も書かない)
        if (! $this->machine->can($submission, SubmissionStatus::Approved)) {
            $this->machine->transition($submission, SubmissionStatus::Approved, $actor);
        }

        return DB::transaction(function () use ($submission, $actor): Submission {
            $this->publish($submission, $actor);
            $this->machine->transition($submission, SubmissionStatus::Approved, $actor);

            if ($submission->user_id !== null) {
                User::query()->whereKey($submission->user_id)->increment('approved_count');
            }
            $this->audit->record(AuditAction::SubmissionPublish, $actor, 'submission', $submission->id, ['type' => $submission->type->value, 'auto' => $actor === null]);
            // 巡回から取り込んだイベントを、人が手を加えずに承認した → 「信頼済み」の提案の数え上げ(自動公開は数えない)
            if ($actor !== null && $submission->number('crawl_source_id') > 0) {
                $this->trust->approvedClean($submission->number('crawl_source_id'));
            }

            return $submission->refresh();
        });
    }

    public function reject(Submission $submission, User $actor, ?string $reason): Submission
    {
        if ($submission->number('crawl_source_id') > 0) {
            $this->trust->rejected($submission->number('crawl_source_id'));
        }

        return $this->machine->transition($submission, SubmissionStatus::Rejected, $actor, $reason);
    }

    /** 却下・自動却下を、審査待ちに戻す(公開はされない) */
    public function restore(Submission $submission, User $actor): Submission
    {
        return $this->machine->transition($submission, SubmissionStatus::InReview, $actor);
    }

    /** 種類ごとに、公開テーブルへ反映する */
    private function publish(Submission $submission, ?User $actor): void
    {
        match ($submission->type) {
            SubmissionType::Spot => $this->publishSpot($submission, $actor),
            SubmissionType::Article => $this->publishArticle($submission, $actor),
            SubmissionType::Correction => $this->applyCorrection($submission, $actor),
            SubmissionType::Comment => $this->publishComment($submission),
            SubmissionType::VisitPhoto => $this->publishVisitPhoto($submission),
            // 情報提供(tip)は、管理者が確認して下書きを作る。承認は「採用した」の印だけ
            SubmissionType::Event => $this->publishEvent($submission, $actor),
            SubmissionType::Tip => null,
        };
    }

    /** 巡回で見つけたイベント: 行事(なければ作る)と開催回を作って公開する。情報元は取得元のページ */
    private function publishEvent(Submission $submission, ?User $actor): void
    {
        $regionId = $submission->number('region_id');
        $title = $submission->text('title');
        $start = $submission->text('start_date');
        abort_if($regionId < 1 || $title === null || $start === null, 422);

        $series = EventSeries::query()->where('title', $title)->where('region_id', $regionId)->first()
            ?? $this->content->saveSeries(null, ['title' => $title, 'recurrence' => Recurrence::Yearly->value, 'region_id' => $regionId], $actor);

        $schedules = [];
        $day = CarbonImmutable::parse($start);
        $end = CarbonImmutable::parse($submission->text('end_date') ?? $start);
        for ($i = 0; $i < 31 && $day->lessThanOrEqualTo($end); $i++, $day = $day->addDay()) {
            $schedules[] = ['date' => $day->toDateString(), 'start_time' => $submission->text('start_time'), 'end_time' => $submission->text('end_time')];
        }

        $url = $submission->text('source_url');
        $event = $this->content->saveEvent(null, [
            'series_id' => $series->id,
            'title' => $title,
            'region_id' => $regionId,
            'venue_name' => $submission->text('venue_name'),
            'address' => $submission->text('address'),
            'fee' => $submission->text('fee'),
            'is_published' => true,
        ], $schedules, [[
            'kind' => 'url', 'url' => $url, 'title' => $submission->text('source_name') ?? (string) parse_url((string) $url, PHP_URL_HOST),
            'checked_at' => now()->toDateString(), 'is_official' => false,
        ]], [], $actor, null, $submission->id);

        $crawlId = $submission->number('crawl_source_id');
        $event->forceFill(['crawl_source_id' => $crawlId > 0 ? $crawlId : null, 'auto_published' => $actor === null && $crawlId > 0])->save();

        if ($submission->text('notice') === 'cancelled') {
            $this->lifecycle->cancelEvent($event, $actor);
        } elseif ($submission->text('notice') === 'postponed') {
            $event->forceFill(['is_postponed' => true])->save();
        }

        $this->link($submission, 'event', $event->id);
    }

    private function publishSpot(Submission $submission, ?User $actor): void
    {
        $p = $submission->payload ?? [];
        $spot = $this->content->saveSpot(null, [
            'title' => $submission->text('title') ?? '',
            'body' => $p['body'] ?? null,
            'region_id' => $submission->number('region_id'),
            'category_id' => $p['category_id'] ?? null,
            'address' => $p['address'] ?? null,
            'lat' => $p['lat'] ?? null,
            'lng' => $p['lng'] ?? null,
            'hours' => $p['hours'] ?? null,
            'access' => $p['access'] ?? null,
            'url' => $p['url'] ?? null,
            'slug' => $submission->text('slug'),
            'is_published' => true,
        ], $this->tags($p['tags'] ?? null), $actor, null, $submission->id);

        $this->credit($spot, $submission);
        $this->attachMedia($submission, $spot);
        $this->link($submission, 'spot', $spot->id);
    }

    private function publishArticle(Submission $submission, ?User $actor): void
    {
        $p = $submission->payload ?? [];
        $article = $this->content->saveArticle(null, [
            'title' => $submission->text('title') ?? '',
            'body' => $p['body'] ?? null,
            'region_id' => $submission->number('region_id'),
            'slug' => $submission->text('slug'),
            'is_published' => true,
        ], $this->tags($p['tags'] ?? null), [], $actor, null, $submission->id);

        $this->credit($article, $submission);
        $this->attachMedia($submission, $article);
        $this->link($submission, 'article', $article->id);
    }

    /** 修正依頼: 対象の項目を直し、履歴(原因 = 投稿の承認)を残す */
    private function applyCorrection(Submission $submission, ?User $actor): void
    {
        $correction = Correction::query()->where('submission_id', $submission->id)->firstOrFail();
        $class = CorrectionFields::model($correction->target_type);
        abort_if($class === null, 404);

        /** @var Event|Spot|Article|Region $target */
        $target = $class::query()->findOrFail($correction->target_id);
        if (! in_array($correction->field, CorrectionFields::for($correction->target_type), true)) {
            abort(422, __('submission.field_not_allowed'));
        }

        $revision = $this->revisions->update(
            $target,
            fn () => $target->forceFill([$correction->field => $correction->proposed_value])->save(),
            // AI が自動で反映した修正は「修正依頼の自動反映」として残し、管理画面の「要確認」に並べる
            $actor === null ? RevisionCause::CorrectionAuto : RevisionCause::Submission,
            $actor,
            null,
            $submission->id,
        );
        if ($target instanceof Event || $target instanceof Spot || $target instanceof Article) {
            $this->content->refreshSearchText($target);
        }

        $correction->forceFill(['applied_revision_id' => $revision?->id])->save();
    }

    private function publishComment(Submission $submission): void
    {
        $p = $submission->payload ?? [];
        $comment = new Comment;
        $comment->forceFill([
            'commentable_type' => $submission->target_type ?? '',
            'commentable_id' => $submission->target_id ?? 0,
            'user_id' => $submission->user_id,
            'body' => $submission->text('body') ?? '',
            'status' => CommentStatus::Published,
        ]);

        $replyId = $submission->number('reply_to_comment_id');
        $replyTo = $replyId > 0 ? Comment::query()->where('id', $replyId)->first() : null;
        if ($replyTo !== null && $replyTo->commentable_type === $comment->commentable_type && $replyTo->commentable_id === $comment->commentable_id) {
            $comment->forceFill(['thread_id' => $replyTo->thread_id ?? $replyTo->id, 'reply_to_comment_id' => $replyTo->id, 'reply_to_user_id' => $replyTo->user_id]);
        }
        $comment->save();
        if ($comment->thread_id === null) {
            $comment->forceFill(['thread_id' => $comment->id])->save();
        }
    }

    /** 「行った!」の写真: 対象の写真に加え、会員なら「行った!」も記録する */
    private function publishVisitPhoto(Submission $submission): void
    {
        $class = CorrectionFields::model($submission->target_type ?? '');
        abort_if($class === null, 404);
        /** @var Event|Spot $target */
        $target = $class::query()->findOrFail($submission->target_id);

        $this->attachMedia($submission, $target);

        if ($submission->user_id !== null) {
            Visit::query()->firstOrCreate(
                ['visitable_type' => $target->getMorphClass(), 'visitable_id' => $target->getKey(), 'user_id' => $submission->user_id],
                ['visited_on' => now()->toDateString()],
            );
        }
    }

    /** 作った公開ページの投稿者を、投稿した会員にする(匿名なら匿名) */
    private function credit(Spot|Article $model, Submission $submission): void
    {
        $model->forceFill(['author_user_id' => $submission->user_id, 'is_anonymous' => $submission->user_id === null])->save();
    }

    /** 処理が終わった画像を、公開コンテンツに付け替える(まだ処理できていない画像は付けない) */
    private function attachMedia(Submission $submission, Model $target): void
    {
        $max = Media::query()->where('mediable_type', $target->getMorphClass())->where('mediable_id', $target->getKey())->max('sort_order');
        $next = is_numeric($max) ? (int) $max : 0;

        foreach ($submission->media as $media) {
            if (! $media->isProcessed()) {
                continue;
            }
            $media->forceFill(['mediable_type' => $target->getMorphClass(), 'mediable_id' => $target->getKey(), 'sort_order' => ++$next])->save();
        }
    }

    private function link(Submission $submission, string $type, int $id): void
    {
        $submission->forceFill(['target_type' => $type, 'target_id' => $id])->save();
    }

    /** @return list<string> */
    private function tags(mixed $text): array
    {
        if (! is_string($text) || $text === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[、,，]/u', $text) ?: []), fn (string $t): bool => $t !== ''));
    }
}
