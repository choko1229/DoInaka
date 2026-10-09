<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use App\Models\EventSchedule;
use Illuminate\Support\Carbon;

/**
 * 日付の表示用の文字(「10月13日(日)」「10月13日(日)〜15日(火)」)。
 */
final class DateText
{
    public static function day(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)->translatedFormat('n月j日(D)');
    }

    /** 一覧のカード用: 最初の日(複数日なら「〜」で終わりの日) */
    public static function range(Event $event): ?string
    {
        $first = $event->schedules->first();
        $last = $event->schedules->last();
        if ($first === null || $last === null) {
            return null;
        }
        if ($first->date->equalTo($last->date)) {
            return self::day($first->date);
        }

        return self::day($first->date).'〜'.self::day($last->date);
    }

    public static function time(EventSchedule $schedule): ?string
    {
        if ($schedule->is_all_day) {
            return __('public.all_day');
        }
        if ($schedule->start_time === null) {
            return null;
        }
        $start = substr($schedule->start_time, 0, 5);

        return $schedule->end_time === null ? $start.'〜' : $start.'〜'.substr($schedule->end_time, 0, 5);
    }
}
