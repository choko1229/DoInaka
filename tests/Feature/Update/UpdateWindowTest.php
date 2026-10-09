<?php

declare(strict_types=1);

use App\Enums\AppMetaKey;
use App\Enums\SettingKey;
use App\Models\PageViewHour;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use App\Services\Update\UpdateWindowCalculator;
use Carbon\CarbonImmutable;

/**
 * 28日分(日本時間)の時間別閲覧数を入れる。$perHour は 0〜23 時ごとの1時間あたりの件数。
 *
 * @param  array<int, int>  $perHour
 */
function seedViews(CarbonImmutable $now, array $perHour, int $days = 28): void
{
    for ($d = 1; $d <= $days; $d++) {
        for ($h = 0; $h < 24; $h++) {
            PageViewHour::query()->create(['hour' => $now->startOfDay()->subDays($d)->addHours($h), 'count' => $perHour[$h] ?? 100]);
        }
    }
}

beforeEach(function (): void {
    $this->now = CarbonImmutable::parse('2026-10-12 12:00:00', 'Asia/Tokyo');
    $this->calc = app(UpdateWindowCalculator::class);
});

it('28日分の時間別閲覧数から、最も少ない1時間を選ぶ', function (): void {
    seedViews($this->now, [14 => 3]);

    expect($this->calc->computeUpdateHour($this->now))->toBe(14);
});

it('28日に満たないときは4時(update.fixed_hour)を使う', function (): void {
    seedViews($this->now, [14 => 3], days: 20);

    expect($this->calc->dataDays($this->now))->toBe(20)
        ->and($this->calc->computeUpdateHour($this->now))->toBe(4);
});

it('データが全くなければ4時', function (): void {
    expect($this->calc->computeUpdateHour($this->now))->toBe(4);
});

it('最少との差が10%以内なら、毎日の重い処理(日本時9時、その1時間前の巡回も)と重ならない時刻を選ぶ', function (): void {
    // 全体は高く(1000)、9・10・11 時だけ同じくらい低い(10)
    seedViews($this->now, array_fill(0, 24, 1000));
    PageViewHour::query()->whereRaw('HOUR(hour) = 9')->update(['count' => 10]);
    PageViewHour::query()->whereRaw('HOUR(hour) = 10')->update(['count' => 10]);
    PageViewHour::query()->whereRaw('HOUR(hour) = 11')->update(['count' => 10]);

    $hour = $this->calc->computeUpdateHour($this->now);

    // 9時は更新自体が重い処理と重なり、10時は1時間前の巡回(9時)が重なる。選ばれるのは11時
    expect($hour)->toBe(11);
});

it('最少が9時だけで他との差が大きいときは、候補が9時しかないので9時を使う', function (): void {
    seedViews($this->now, []);
    PageViewHour::query()->whereRaw('HOUR(hour) = 9')->update(['count' => 10]);

    expect($this->calc->computeUpdateHour($this->now))->toBe(9);
});

it('巡回は更新の1時間前になる', function (): void {
    expect($this->calc->crawlHourFor(4))->toBe(3)
        ->and($this->calc->crawlHourFor(0))->toBe(23);
});

it('毎週の計算結果を app_meta に保存し、updateHour() が読む', function (): void {
    seedViews($this->now, [14 => 3]);

    expect($this->calc->recalculate($this->now))->toBe(14)
        ->and(app(AppMetaService::class)->get(AppMetaKey::UpdateWindowHour))->toBe('14')
        ->and(app(AppMetaService::class)->get(AppMetaKey::CrawlWindowHour))->toBe('13')
        ->and($this->calc->updateHour())->toBe(14);
});

it('管理者が時刻を固定すると、計算結果より固定の時刻を使う', function (): void {
    seedViews($this->now, [14 => 3]);
    $this->calc->recalculate($this->now);

    app(SettingsService::class)->set(SettingKey::UpdateWindowMode, 'fixed');
    app(SettingsService::class)->set(SettingKey::UpdateFixedHour, 2);

    expect($this->calc->updateHour())->toBe(2)
        ->and($this->calc->computeUpdateHour($this->now))->toBe(2);
});

it('時刻ごとの平均は、日本時間の時刻で集計する', function (): void {
    // UTC 15:00 は日本時間 0:00
    PageViewHour::query()->create(['hour' => CarbonImmutable::parse('2026-10-10 15:00:00', 'UTC'), 'count' => 280]);

    $averages = $this->calc->hourlyAverages($this->now);

    expect($averages[0])->toBe(10.0)->and($averages[15])->toBe(0.0);
});
