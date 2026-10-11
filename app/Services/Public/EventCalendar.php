<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Contracts\SearchEngine;
use App\Models\Event;
use App\Services\Search\SearchQuery;
use Carbon\CarbonImmutable;

/**
 * イベント一覧のカレンダー表示(月の格子)。同じ絞り込み条件で、その月に日程のあるイベントを、日ごとに並べる。
 */
final class EventCalendar
{
    /** 1か月に出すイベントの上限(50件 × 4ページ) */
    private const MAX_PAGES = 4;

    public function __construct(private readonly SearchEngine $search) {}

    /**
     * @return array{month: CarbonImmutable, prev: string, next: string, weeks: list<list<array{date: CarbonImmutable, inMonth: bool, today: bool, events: list<Event>}>>, total: int}
     */
    public function month(SearchQuery $query, ?string $month): array
    {
        $first = $this->parse($month)->startOfMonth();
        $last = $first->endOfMonth();

        /** @var array<string, list<Event>> $byDate */
        $byDate = [];
        $seen = [];
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $result = $this->search->events($query->forRange($first->toDateString(), $last->toDateString(), $page, 50));
            foreach ($result->items() as $event) {
                if (isset($seen[$event->id])) {
                    continue;
                }
                $seen[$event->id] = true;
                $event->loadMissing('schedules');
                foreach ($event->schedules as $schedule) {
                    $date = CarbonImmutable::parse($schedule->date)->toDateString();
                    if ($date >= $first->toDateString() && $date <= $last->toDateString()) {
                        $byDate[$date][] = $event;
                    }
                }
            }
            if (! $result->hasMorePages()) {
                break;
            }
        }

        $weeks = [];
        $cursor = $first->startOfWeek(CarbonImmutable::SUNDAY);
        $end = $last->endOfWeek(CarbonImmutable::SATURDAY);
        $today = CarbonImmutable::now()->toDateString();
        while ($cursor <= $end) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'date' => $cursor,
                    'inMonth' => $cursor->month === $first->month,
                    'today' => $cursor->toDateString() === $today,
                    'events' => $byDate[$cursor->toDateString()] ?? [],
                ];
                $cursor = $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return [
            'month' => $first,
            'prev' => $first->subMonth()->format('Y-m'),
            'next' => $first->addMonth()->format('Y-m'),
            'weeks' => $weeks,
            'total' => count($seen),
        ];
    }

    private function parse(?string $month): CarbonImmutable
    {
        if ($month !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1) {
            return CarbonImmutable::createFromFormat('Y-m-d', $month.'-01') ?: CarbonImmutable::now();
        }

        return CarbonImmutable::now();
    }
}
