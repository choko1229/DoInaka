<?php

declare(strict_types=1);

namespace App\Services\Update;

use App\Enums\AppMetaKey;
use App\Enums\SettingKey;
use App\Models\PageViewHour;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use Carbon\CarbonImmutable;

/**
 * 自動更新と巡回の時間帯(アクセスが最も少ない1時間)を計算する(設計書10.4)。
 *
 * - 直近28日の時間別閲覧数を、時刻(0〜23時・日本時間)ごとの1日あたりの平均にして、最も少ない時刻を選ぶ
 * - 28日分がそろうまでは固定の時刻(update.fixed_hour。初期値4時)。管理者が固定にすることもできる
 * - 最少との差が10%以内の時刻が複数あれば、毎日の重い処理(日本時9時の AI 枠リセットなど)と重ならない時刻を選ぶ
 * - 更新はその時刻のはじめ、巡回はその1時間前
 */
class UpdateWindowCalculator
{
    public const DAYS = 28;

    private const TOLERANCE = 0.10;

    /** 毎日動く重い処理の時刻(日本時間)。人気スコア集計は毎時なので除く */
    private const HEAVY_HOURS = [9];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly AppMetaService $meta,
    ) {}

    /**
     * 時刻ごとの1日あたりの平均閲覧数。
     *
     * @return array<int, float> 0〜23 をキーにした平均
     */
    public function hourlyAverages(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now('Asia/Tokyo');
        $since = $now->subDays(self::DAYS)->startOfHour();

        $totals = array_fill(0, 24, 0);
        foreach (PageViewHour::query()->where('hour', '>=', $since)->get() as $row) {
            $hour = (int) $row->hour->setTimezone('Asia/Tokyo')->format('G');
            $totals[$hour] += $row->count;
        }

        return array_map(fn (int $total): float => $total / self::DAYS, $totals);
    }

    /** 集計のある日数(日本時間の日付の種類)。28以上でそろったとみなす */
    public function dataDays(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now('Asia/Tokyo');
        $first = PageViewHour::query()->min('hour');
        if (! is_string($first)) {
            return 0;
        }

        return (int) min(self::DAYS, CarbonImmutable::parse($first, 'Asia/Tokyo')->startOfDay()->diffInDays($now->startOfDay()));
    }

    /**
     * 更新を始める時刻(0〜23)を計算する。
     */
    public function computeUpdateHour(?CarbonImmutable $now = null): int
    {
        $fixed = $this->settings->int(SettingKey::UpdateFixedHour);

        if ($this->settings->string(SettingKey::UpdateWindowMode) === 'fixed' || $this->dataDays($now) < self::DAYS) {
            return $fixed;
        }

        $averages = $this->hourlyAverages($now);
        $min = min([...$averages, PHP_FLOAT_MAX]);
        $limit = $min * (1 + self::TOLERANCE);

        $candidates = array_keys(array_filter($averages, fn (float $avg): bool => $avg <= $limit));
        sort($candidates);

        $free = array_values(array_filter($candidates, fn (int $hour): bool => ! $this->collidesWithHeavy($hour)));
        $pool = $free !== [] ? $free : $candidates;

        // 候補の中で最も少ない時刻(同じなら早い方)
        usort($pool, fn (int $a, int $b): int => [$averages[$a], $a] <=> [$averages[$b], $b]);

        return $pool[0];
    }

    /** 巡回は更新の1時間前 */
    public function crawlHourFor(int $updateHour): int
    {
        return ($updateHour + 23) % 24;
    }

    /**
     * 計算して app_meta に保存する(毎週月曜)。
     */
    public function recalculate(?CarbonImmutable $now = null): int
    {
        $hour = $this->computeUpdateHour($now);
        $this->meta->set(AppMetaKey::UpdateWindowHour, (string) $hour);
        $this->meta->set(AppMetaKey::CrawlWindowHour, (string) $this->crawlHourFor($hour));

        return $hour;
    }

    /**
     * いま使う更新の時刻。固定モードなら設定、自動なら保存した計算結果(なければ固定の時刻)。
     */
    public function updateHour(): int
    {
        if ($this->settings->string(SettingKey::UpdateWindowMode) === 'fixed') {
            return $this->settings->int(SettingKey::UpdateFixedHour);
        }

        $saved = $this->meta->get(AppMetaKey::UpdateWindowHour);

        return $saved !== null && ctype_digit($saved) && (int) $saved < 24 ? (int) $saved : $this->settings->int(SettingKey::UpdateFixedHour);
    }

    private function collidesWithHeavy(int $hour): bool
    {
        // 更新の時刻と、その1時間前の巡回のどちらも重い処理と重ならないようにする
        return in_array($hour, self::HEAVY_HOURS, true) || in_array($this->crawlHourFor($hour), self::HEAVY_HOURS, true);
    }
}
