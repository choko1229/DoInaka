<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\EventStatus;
use App\Enums\Recurrence;
use App\Enums\RevisionCause;
use App\Exceptions\EventSourceMissingException;
use App\Models\Article;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\EventSource;
use App\Models\Spot;
use App\Models\User;
use App\Services\Search\SearchTextBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 管理者が行事・開催回・スポット・記事を登録・編集するときの処理。
 * 公開データの変更は、必ず RevisionService を通して、前後の内容を履歴に残す。
 * 保存のたびに検索用の search_text も作り直す。
 */
class ContentService
{
    public function __construct(
        private readonly RevisionService $revisions,
        private readonly RevisionSnapshot $snapshot,
        private readonly SearchTextBuilder $searchText,
    ) {}

    /**
     * @param  array{title: string, slug?: string|null, summary?: string|null, recurrence: string, region_id: int, category_id?: int|null}  $data
     */
    public function saveSeries(?EventSeries $series, array $data, ?User $actor = null, ?string $reason = null): EventSeries
    {
        $attributes = [
            'title' => $data['title'],
            'slug' => $this->slug($data['slug'] ?? null),
            'summary' => $data['summary'] ?? null,
            'recurrence' => Recurrence::from($data['recurrence']),
            'region_id' => $data['region_id'],
            'category_id' => $data['category_id'] ?? null,
        ];

        if ($series === null) {
            return DB::transaction(function () use ($attributes, $actor, $reason): EventSeries {
                $series = new EventSeries($attributes);
                $series->forceFill(['created_by' => $actor?->id])->save();
                $this->revisions->recordCreated($series, $actor, $reason);

                return $series;
            });
        }

        $this->revisions->update($series, fn () => $series->forceFill($attributes)->save(), actor: $actor, reason: $reason);

        return $series->refresh();
    }

    /**
     * 開催回を保存する(新規・更新)。日程・情報元・タグも、ここで置き換える。
     *
     * 状態の自動的な決まり:
     *  - すべての日が中止なら「中止」。1日でも戻せば「予定」に戻る
     *  - 公開中の開催回で、すでにあった日付が変わる・なくなると「延期」になる(日を足しただけでは延期にしない)
     *  - 公開は、情報元が1件以上あるときだけ(なければ EventSourceMissingException)
     *
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $schedules
     * @param  list<array<string, mixed>>  $sources
     * @param  list<string>  $tags
     */
    public function saveEvent(?Event $event, array $data, array $schedules, array $sources, array $tags, ?User $actor = null, ?string $reason = null): Event
    {
        $isNew = $event === null;
        $event ??= new Event;

        // 公開する(または公開したまま)のに、情報元が1件もないものは保存しない
        if (((bool) ($data['is_published'] ?? $event->is_published)) && $sources === []) {
            throw new EventSourceMissingException(__('content.event_source_required'));
        }

        $oldDates = $isNew ? [] : $this->dates($event);
        $wasPublished = ! $isNew && $event->is_published;

        $change = function () use ($event, $data, $schedules, $sources, $tags, $oldDates, $wasPublished, $actor, $isNew): void {
            $event->fill($this->eventAttributes($data));
            if ($isNew) {
                $event->status = EventStatus::Scheduled;
                $event->forceFill(['author_user_id' => $actor?->id, 'is_published' => false]);
            }
            $event->save();

            $this->replaceSchedules($event, $schedules);
            $this->replaceSources($event, $sources);
            $event->unsetRelation('schedules')->unsetRelation('sources');
            $this->snapshot->syncTags($event, $tags);

            $this->settleStatus($event, $oldDates, $wasPublished, $data);

            $publish = (bool) ($data['is_published'] ?? $event->is_published);
            if ($publish && ! $event->is_published) {
                $event->publish();
            } elseif (! $publish && $event->is_published) {
                $event->unpublish();
            }

            $this->refreshSearchText($event);
        };

        if ($isNew) {
            DB::transaction(function () use ($change, $event, $actor, $reason): void {
                $change();
                $this->revisions->recordCreated($event, $actor, $reason);
            });

            return $event->refresh();
        }

        $this->revisions->update($event, $change, actor: $actor, reason: $reason);

        return $event->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $tags
     */
    public function saveSpot(?Spot $spot, array $data, array $tags, ?User $actor = null, ?string $reason = null, ?int $submissionId = null): Spot
    {
        $isNew = $spot === null;
        $spot ??= new Spot;

        $change = function () use ($spot, $data, $tags, $actor, $isNew): void {
            $spot->fill($this->spotAttributes($data));
            if ($isNew) {
                $spot->forceFill(['author_user_id' => $actor?->id]);
            }
            $this->applyPublishFlag($spot, (bool) ($data['is_published'] ?? false));
            $spot->save();
            $this->snapshot->syncTags($spot, $tags);
            $this->refreshSearchText($spot);
        };

        return $this->persist($spot, $isNew, $change, $actor, $reason, $submissionId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $tags
     * @param  list<array{related_type: string, related_id: int}>  $relations
     */
    public function saveArticle(?Article $article, array $data, array $tags, array $relations, ?User $actor = null, ?string $reason = null, ?int $submissionId = null): Article
    {
        $isNew = $article === null;
        $article ??= new Article;

        $change = function () use ($article, $data, $tags, $relations, $actor, $isNew): void {
            $article->fill([
                'title' => $data['title'],
                'slug' => $this->slug(is_string($data['slug'] ?? null) ? $data['slug'] : null),
                'body' => $data['body'] ?? null,
                'region_id' => $data['region_id'],
            ]);
            if ($isNew) {
                $article->forceFill(['author_user_id' => $actor?->id]);
            }
            $this->applyPublishFlag($article, (bool) ($data['is_published'] ?? false));
            $article->save();
            $this->snapshot->syncTags($article, $tags);

            $article->relations()->delete();
            foreach ($relations as $relation) {
                $article->relations()->create($relation);
            }
            $this->refreshSearchText($article);
        };

        return $this->persist($article, $isNew, $change, $actor, $reason, $submissionId);
    }

    /** 検索用のテキストを作り直す(保存・履歴から戻したあと) */
    public function refreshSearchText(Event|Spot|Article $model): void
    {
        $this->searchText->refresh($model);
    }

    /**
     * 「公開」の URL に使う英字部分: 英小文字・数字・ハイフンだけにする。空なら null。
     */
    public function slug(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');

        return $slug === '' ? null : mb_substr($slug, 0, 120);
    }

    /**
     * @template T of Spot|Article
     *
     * @param  T  $model
     * @param  \Closure(): mixed  $change
     * @return T
     */
    private function persist(Spot|Article $model, bool $isNew, \Closure $change, ?User $actor, ?string $reason, ?int $submissionId = null): Spot|Article
    {
        // 投稿の承認で作る・直すときは、履歴に投稿の ID を残す(原因は「投稿の承認」)
        $cause = $submissionId === null ? RevisionCause::AdminEdit : RevisionCause::Submission;

        if ($isNew) {
            DB::transaction(function () use ($change, $model, $actor, $reason, $submissionId, $cause): void {
                $change();
                $this->revisions->recordCreated($model, $actor, $reason, $submissionId, $submissionId === null ? RevisionCause::Created : $cause);
            });

            return $model->refresh();
        }

        $this->revisions->update($model, $change, $cause, $actor, $reason, $submissionId);

        return $model->refresh();
    }

    private function applyPublishFlag(Spot|Article $model, bool $publish): void
    {
        if ($publish && ! $model->is_published) {
            $model->published_at ??= now();
        }
        $model->is_published = $publish;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function eventAttributes(array $data): array
    {
        return [
            'series_id' => $data['series_id'],
            'title' => $data['title'],
            'slug' => $this->slug(is_string($data['slug'] ?? null) ? $data['slug'] : null),
            'body' => $data['body'] ?? null,
            'region_id' => $data['region_id'],
            'category_id' => $data['category_id'] ?? null,
            'venue_name' => $data['venue_name'] ?? null,
            'address' => $data['address'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'fee' => $data['fee'] ?? null,
            'url' => $data['url'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function spotAttributes(array $data): array
    {
        return [
            'title' => $data['title'],
            'slug' => $this->slug(is_string($data['slug'] ?? null) ? $data['slug'] : null),
            'body' => $data['body'] ?? null,
            'region_id' => $data['region_id'],
            'category_id' => $data['category_id'] ?? null,
            'address' => $data['address'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'hours' => $data['hours'] ?? null,
            'access' => $data['access'] ?? null,
            'url' => $data['url'] ?? null,
        ];
    }

    /**
     * 情報元を置き換える。公開中のイベントの情報元が一瞬でも0件にならないよう、新しいものを先に作ってから古いものを消す。
     *
     * @param  list<array<string, mixed>>  $sources
     */
    private function replaceSources(Event $event, array $sources): void
    {
        $oldIds = $event->sources()->pluck('id')->all();
        foreach ($sources as $source) {
            $event->sources()->create($source);
        }
        if ($oldIds !== []) {
            EventSource::query()->whereIn('id', $oldIds)->delete();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $schedules
     */
    private function replaceSchedules(Event $event, array $schedules): void
    {
        $event->schedules()->delete();
        foreach ($schedules as $row) {
            $event->schedules()->create([
                'date' => $row['date'],
                'start_time' => $row['start_time'] ?? null,
                'end_time' => $row['end_time'] ?? null,
                'is_all_day' => (bool) ($row['is_all_day'] ?? false),
                'note' => $row['note'] ?? null,
                'is_cancelled' => (bool) ($row['is_cancelled'] ?? false),
            ]);
        }
    }

    /**
     * 日程の変更から、状態(予定・中止・開催済み)と「延期」を決める。
     *
     * @param  list<string>  $oldDates
     * @param  array<string, mixed>  $data
     */
    private function settleStatus(Event $event, array $oldDates, bool $wasPublished, array $data): void
    {
        $event->load('schedules');
        $newDates = $this->dates($event);

        if ($event->isFullyCancelled()) {
            $event->status = EventStatus::Cancelled;
        } elseif ($event->status === EventStatus::Cancelled || $event->status === EventStatus::Undecided) {
            $event->status = $newDates === [] ? EventStatus::Undecided : EventStatus::Scheduled;
        } elseif ($event->status === EventStatus::Ended && $newDates !== [] && max($newDates) >= Carbon::today('Asia/Tokyo')->toDateString()) {
            $event->status = EventStatus::Scheduled;
        }

        // すでにあった日付が変わる・なくなった → 延期(日を足しただけなら延期にしない)
        $removed = array_diff($oldDates, $newDates);
        if ($wasPublished && $oldDates !== [] && $removed !== [] && $newDates !== []) {
            $event->is_postponed = true;
            $event->forceFill(['postponed_from' => min($oldDates)]);
        }

        // 管理者が「延期」を付けた・外したときは、自動の判定より優先する
        if (array_key_exists('is_postponed', $data) && $data['is_postponed'] === false) {
            $event->is_postponed = false;
            $event->forceFill(['postponed_from' => null]);
        } elseif (array_key_exists('is_postponed', $data) && $data['is_postponed'] === true && ! $event->is_postponed) {
            $event->is_postponed = true;
            $event->forceFill(['postponed_from' => $oldDates === [] ? null : min($oldDates)]);
        }

        $event->save();
    }

    /**
     * @return list<string> 日付(Y-m-d)の昇順
     */
    private function dates(Event $event): array
    {
        return array_values($event->schedules()->get()->map(fn ($s): string => $s->date->toDateString())->unique()->sort()->all());
    }
}
