<?php

declare(strict_types=1);

namespace App\Services\Crawl;

use App\Enums\SubmissionAction;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\Correction;
use App\Models\CrawlSource;
use App\Models\Event;
use App\Models\Region;
use App\Models\Submission;
use App\Services\Ai\EventDraft;
use App\Services\Region\RegionScope;
use App\Services\Submission\ReviewService;
use App\Services\Submission\SubmissionStateMachine;
use App\Support\ReceiptNumber;
use Carbon\CarbonImmutable;

/**
 * 巡回・情報提供の読み取りで見つけたイベントを、審査に取り込む(設計書9.6・9.7)。
 *
 * - 今日から1年先までのイベントだけ。終わった行事・それより先は入れない
 * - 既存の行事と同じものは、新しい行事にせず、公式の値で「修正依頼」を作る(反映は管理者が決める)
 * - 新しい行事は、イベント(event)の投稿として審査へ。信頼済みの情報源だけ、自動で公開する
 *   ただし「日時・場所が読めない」「確信度 0.9 未満」「既存の行事と食い違う」「中止・延期の知らせ」は人の審査へ
 */
final class CrawlImporter
{
    public const AUTO_PUBLISH_MIN_CONFIDENCE = 0.9;

    public function __construct(
        private readonly SubmissionStateMachine $machine,
        private readonly ReviewService $review,
        private readonly RegionScope $scope,
    ) {}

    /**
     * @return array{result: 'skipped'|'correction'|'review'|'published', submissions: int}
     */
    public function import(CrawlSource $source, EventDraft $draft, ?string $pageUrl = null): array
    {
        $skip = ['result' => 'skipped', 'submissions' => 0];
        if (! $draft->isEvent || $draft->title === null || $draft->startDate === null || ! $this->inWindow($draft)) {
            return $skip;
        }

        $region = $source->region;
        $existing = $this->findExisting($draft, $region);
        if ($existing !== null) {
            $count = $this->createCorrections($existing, $draft, $pageUrl ?? $source->url);

            return ['result' => 'correction', 'submissions' => $count];
        }

        $submission = $this->createEventSubmission($source, $draft, $pageUrl ?? $source->url);

        if ($this->canAutoPublish($source, $draft)) {
            $this->review->approve($submission, null);

            return ['result' => 'published', 'submissions' => 1];
        }

        return ['result' => 'review', 'submissions' => 1];
    }

    /** 今日から1年先までか(終わった行事・それより先は取り込まない) */
    public function inWindow(EventDraft $draft, ?CarbonImmutable $today = null): bool
    {
        $today ??= CarbonImmutable::now('Asia/Tokyo')->startOfDay();
        if ($draft->startDate === null) {
            return false;
        }

        $start = CarbonImmutable::parse($draft->startDate, 'Asia/Tokyo');
        $end = $draft->endDate !== null ? CarbonImmutable::parse($draft->endDate, 'Asia/Tokyo') : $start;

        return $end->greaterThanOrEqualTo($today) && $start->lessThanOrEqualTo($today->addYear());
    }

    private function canAutoPublish(CrawlSource $source, EventDraft $draft): bool
    {
        return $source->is_trusted && ! $source->isPaused() && $source->is_active
            && $draft->confidence >= self::AUTO_PUBLISH_MIN_CONFIDENCE
            && $draft->hasDateAndPlace()
            && ! $draft->isCancelled && ! $draft->isPostponed;
    }

    /** 名前と日付(前後1日)が同じ公開済みの開催回。AI が既存の ID を示したときは、その ID を優先する */
    private function findExisting(EventDraft $draft, ?Region $region): ?Event
    {
        $ids = $region === null ? null : $this->scope->ids($this->prefecture($region));

        if ($draft->existingEventId !== null) {
            $hinted = Event::query()->where('is_published', true)->when($ids !== null, fn ($q) => $q->whereIn('region_id', $ids))->find($draft->existingEventId);
            if ($hinted !== null) {
                return $hinted;
            }
        }

        $start = CarbonImmutable::parse((string) $draft->startDate);

        return Event::query()
            ->where('is_published', true)->where('title', $draft->title)
            ->when($ids !== null, fn ($q) => $q->whereIn('region_id', $ids))
            ->whereHas('schedules', fn ($q) => $q->whereBetween('date', [$start->subDay()->toDateString(), $start->addDay()->toDateString()]))
            ->first();
    }

    private function prefecture(Region $region): Region
    {
        $node = $region;
        $guard = 0;
        while ($node->parent_id !== null && $guard++ < 5) {
            $node = $node->parent()->firstOrFail();
        }

        return $node;
    }

    /** 公式の値と違う項目ごとに、修正依頼を作る(公式の値を優先。反映は管理者が決める) */
    private function createCorrections(Event $event, EventDraft $draft, string $sourceUrl): int
    {
        $values = ['venue_name' => $draft->venue, 'address' => $draft->address, 'fee' => $draft->fee, 'url' => $draft->url];
        $count = 0;

        foreach ($values as $field => $proposed) {
            $current = $event->getAttribute($field);
            if ($proposed === null || trim(is_scalar($current) ? (string) $current : '') === $proposed) {
                continue;
            }
            // 同じ内容の依頼がすでに待っていれば、重ねない
            $already = Correction::query()->where('target_type', 'event')->where('target_id', $event->id)->where('field', $field)->where('proposed_value', $proposed)
                ->whereHas('submission', fn ($q) => $q->whereIn('status', [SubmissionStatus::InReview, SubmissionStatus::Received]))->exists();
            if ($already) {
                continue;
            }

            $submission = $this->open(SubmissionType::Correction, ['field' => $field, 'proposed_value' => $proposed, 'source_url' => $sourceUrl, 'origin' => 'crawl'], 'event', $event->id);
            Correction::query()->create(['submission_id' => $submission->id, 'target_type' => 'event', 'target_id' => $event->id, 'field' => $field, 'proposed_value' => $proposed, 'source_url' => $sourceUrl]);
            $this->machine->transition($submission, SubmissionStatus::InReview);
            $count++;
        }

        return $count;
    }

    private function createEventSubmission(CrawlSource $source, EventDraft $draft, string $sourceUrl): Submission
    {
        $payload = array_filter([
            'title' => $draft->title, 'region_id' => $source->region_id, 'venue_name' => $draft->venue, 'address' => $draft->address,
            'fee' => $draft->fee, 'organizer' => $draft->organizer, 'start_date' => $draft->startDate, 'end_date' => $draft->endDate,
            'start_time' => $draft->startTime, 'end_time' => $draft->endTime, 'source_url' => $sourceUrl, 'source_name' => $source->name,
            'crawl_source_id' => $source->id, 'confidence' => $draft->confidence,
            'notice' => $draft->isCancelled ? 'cancelled' : ($draft->isPostponed ? 'postponed' : null),
        ], fn (mixed $v): bool => $v !== null);

        $submission = $this->open(SubmissionType::Event, $payload, null, null);
        $this->machine->transition($submission, SubmissionStatus::InReview);

        return $submission;
    }

    /** @param  array<string, mixed>  $payload */
    private function open(SubmissionType $type, array $payload, ?string $targetType, ?int $targetId): Submission
    {
        return $this->machine->open([
            'receipt_no' => ReceiptNumber::generate(),
            'type' => $type,
            'action' => SubmissionAction::Create,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'payload' => $payload,
            'user_id' => null,
            'ip_hash' => null,
        ]);
    }
}
