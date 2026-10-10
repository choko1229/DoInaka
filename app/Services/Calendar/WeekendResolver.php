<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * 「今週末」の日付の範囲(設計書11.2)。
 *
 * 直近の土曜・日曜に、前後につながる祝日・振替休日を足した連休(金曜が祝日なら金〜日、月曜が祝日なら土〜月、GW は全部つながる)。
 *  - いまが土日・祝日なら、いまを含む連休。そうでなければ、次の土曜を含む連休
 *  - 日付は日本時間で判定する(日曜の23:59はまだ今週末。月曜の0:00から次の週末)
 *  - 連休の前の金曜の夜(17時以降に始まる日程)も、今週末に含める(`fridayNightFrom`)
 */
class WeekendResolver
{
    /** 金曜の夜とみなす開始時刻 */
    public const FRIDAY_NIGHT_FROM = '17:00:00';

    /**
     * @return array{from: CarbonImmutable, to: CarbonImmutable, friday_night: CarbonImmutable|null}
     *                                                                                               friday_night は、この日の FRIDAY_NIGHT_FROM 以降の日程も含める日(連休が土曜から始まるときの前の金曜。金曜が連休に入っていれば null)
     */
    public function resolve(CarbonInterface $now): array
    {
        $today = CarbonImmutable::instance($now)->setTimezone('Asia/Tokyo')->startOfDay();
        $holidays = $this->holidaysAround($today);

        $isOff = fn (CarbonImmutable $d): bool => $d->isWeekend() || isset($holidays[$d->toDateString()]);

        // 起点: いまが休みの日なら今日、そうでなければ次の土曜
        $anchor = $isOff($today) ? $today : $today->next(CarbonImmutable::SATURDAY);

        // 起点を含む「土日を含む連休」を探す。祝日だけの連休(平日の祝日1日)は、週末としては扱わない
        $from = $anchor;
        while ($isOff($from->subDay())) {
            $from = $from->subDay();
        }
        $to = $anchor;
        while ($isOff($to->addDay())) {
            $to = $to->addDay();
        }

        if (! $this->hasWeekendDay($from, $to)) {
            $saturday = $anchor->next(CarbonImmutable::SATURDAY);

            return $this->resolve($saturday);
        }

        $friday = $from->subDay();
        $fridayNight = $from->isSaturday() && ! $isOff($friday) ? $friday : null;

        return ['from' => $from, 'to' => $to, 'friday_night' => $fridayNight];
    }

    /**
     * @return array<string, true> 日付(Y-m-d)の集合
     */
    private function holidaysAround(CarbonImmutable $today): array
    {
        $dates = DB::table('holidays')
            ->whereBetween('date', [$today->subDays(14)->toDateString(), $today->addDays(60)->toDateString()])
            ->pluck('date')
            ->map(fn (mixed $d): string => is_string($d) ? substr($d, 0, 10) : '')
            ->all();

        return array_fill_keys($dates, true);
    }

    private function hasWeekendDay(CarbonImmutable $from, CarbonImmutable $to): bool
    {
        for ($day = $from; $day <= $to; $day = $day->addDay()) {
            if ($day->isWeekend()) {
                return true;
            }
        }

        return false;
    }
}
