<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\EventStatus;
use App\Enums\Recurrence;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * 開催回の状態の変更: 中止にする(消さずに表示だけ変える)、来年分を作る、終わった開催回を「開催済み」にする。
 * どれも RevisionService を通して、履歴に残す。
 */
class EventLifecycle
{
    public function __construct(
        private readonly RevisionService $revisions,
        private readonly ContentService $content,
    ) {}

    /** 1日分を「中止」にする。すべての日が中止になれば、開催回も「中止」になる */
    public function cancelDay(EventSchedule $schedule, ?User $actor = null, ?string $reason = null): void
    {
        $this->setDayCancelled($schedule, true, $actor, $reason);
    }

    /** 中止を取り消す。開催回が「中止」だったら「予定」に戻る */
    public function restoreDay(EventSchedule $schedule, ?User $actor = null, ?string $reason = null): void
    {
        $this->setDayCancelled($schedule, false, $actor, $reason);
    }

    /** 開催回ごと「中止」にする(すべての日を中止にする。消さない) */
    public function cancelEvent(Event $event, ?User $actor = null, ?string $reason = null): void
    {
        $this->revisions->update($event, function () use ($event): void {
            $event->schedules()->update(['is_cancelled' => true]);
            $event->forceFill(['status' => EventStatus::Cancelled])->save();
            $this->content->refreshSearchText($event);
        }, actor: $actor, reason: $reason);
    }

    /**
     * 去年の回をコピーして、来年分を作る。日付は1年進める(2月29日は、来年に29日がなければ2月28日)。
     * 作られるのは公開前の下書き。情報元は引き継ぐが、確認した日は空にして、確かめ直してもらう。
     */
    public function copyForNextYear(Event $event, ?User $actor = null): Event
    {
        return DB::transaction(function () use ($event, $actor): Event {
            $event->load(['schedules', 'sources', 'tags']);

            /** @var list<array<string, mixed>> $schedules */
            $schedules = array_values($event->schedules->map(fn (EventSchedule $s): array => [
                'date' => $s->date->copy()->addYearNoOverflow()->toDateString(),
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'is_all_day' => $s->is_all_day,
                'note' => $s->note,
                'is_cancelled' => false,
            ])->all());
            /** @var list<array<string, mixed>> $sources */
            $sources = array_values($event->sources->map(fn ($s): array => [
                'kind' => $s->kind->value, 'url' => $s->url, 'title' => $s->title, 'media_id' => $s->media_id,
                'checked_at' => null, 'is_official' => $s->is_official,
            ])->all());
            $tags = array_values(array_map(fn (mixed $n): string => is_string($n) ? $n : '', $event->tags->pluck('name')->all()));

            $copy = $this->content->saveEvent(
                null,
                [
                    'series_id' => $event->series_id,
                    'title' => $event->title,
                    'slug' => $event->slug,
                    'body' => $event->body,
                    'region_id' => $event->region_id,
                    'category_id' => $event->category_id,
                    'venue_name' => $event->venue_name,
                    'address' => $event->address,
                    'lat' => $event->lat,
                    'lng' => $event->lng,
                    'fee' => $event->fee,
                    'url' => $event->url,
                    'is_published' => false,
                ],
                $schedules,
                $sources,
                $tags,
                $actor,
                '去年の回をコピー',
            );

            return $copy;
        });
    }

    /**
     * 最終日を過ぎた開催回を「開催済み」にする(毎時の定期処理)。
     * 毎年開催の行事で、これより新しい開催回がまだなければ、「次回未定」の下書きを作る。
     *
     * @return array{ended: int, drafts: int}
     */
    public function finishEnded(?CarbonInterface $now = null): array
    {
        $today = ($now ?? now())->copy()->setTimezone('Asia/Tokyo')->toDateString();
        $ended = 0;
        $drafts = 0;

        $candidates = Event::query()
            ->where('status', EventStatus::Scheduled->value)
            ->whereHas('schedules')
            ->whereDoesntHave('schedules', fn ($q) => $q->where('date', '>=', $today))
            ->with(['series', 'schedules'])
            ->get();

        foreach ($candidates as $event) {
            $this->revisions->update($event, fn () => $event->forceFill(['status' => EventStatus::Ended])->save(), reason: '最終日を過ぎた');
            $ended++;

            if ($this->needsNextDraft($event)) {
                $this->createUndecidedDraft($event);
                $drafts++;
            }
        }

        return ['ended' => $ended, 'drafts' => $drafts];
    }

    private function setDayCancelled(EventSchedule $schedule, bool $cancelled, ?User $actor, ?string $reason): void
    {
        $event = $schedule->event()->firstOrFail();

        $this->revisions->update($event, function () use ($event, $schedule, $cancelled): void {
            $schedule->forceFill(['is_cancelled' => $cancelled])->save();
            $event->unsetRelation('schedules');

            if ($cancelled && $event->isFullyCancelled()) {
                $event->forceFill(['status' => EventStatus::Cancelled])->save();
            } elseif (! $cancelled && $event->status === EventStatus::Cancelled) {
                $event->forceFill(['status' => EventStatus::Scheduled])->save();
            }
            $this->content->refreshSearchText($event);
        }, actor: $actor, reason: $reason);
    }

    private function needsNextDraft(Event $event): bool
    {
        if ($event->series()->firstOrFail()->recurrence !== Recurrence::Yearly) {
            return false;
        }

        $lastDate = $event->lastDate()?->toDateString();

        // これより新しい開催回(次回未定の下書きを含む)がすでにあれば作らない
        return ! Event::query()
            ->where('series_id', $event->series_id)
            ->where('id', '!=', $event->id)
            ->where(function ($q) use ($lastDate): void {
                $q->where('status', EventStatus::Undecided->value)
                    ->orWhereHas('schedules', fn ($s) => $s->where('date', '>', $lastDate));
            })
            ->exists();
    }

    private function createUndecidedDraft(Event $event): void
    {
        $draft = $event->replicate(['view_count', 'favorite_count', 'visited_count', 'popularity_score', 'published_at', 'search_text', 'is_published', 'is_postponed', 'postponed_from', 'status']);
        $draft->forceFill(['status' => EventStatus::Undecided, 'is_published' => false, 'is_postponed' => false]);
        $draft->save();
        $this->revisions->recordCreated($draft, null, '毎年開催の行事: 次回未定の下書き');
        $this->content->refreshSearchText($draft);
    }
}
