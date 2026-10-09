<?php

declare(strict_types=1);

use App\Enums\Season;
use App\Enums\Theme;
use App\Enums\ThemePreference;
use App\Services\Design\ThemeResolver;
use Carbon\CarbonImmutable;

function jst(string $datetime): CarbonImmutable
{
    return CarbonImmutable::parse($datetime, 'Asia/Tokyo');
}

it('時間帯の切り替え時刻ちょうどと1分前を判定できる', function (string $at, Theme $expected): void {
    expect((new ThemeResolver)->themeAt(jst($at)))->toBe($expected);
})->with([
    '4:59 は夜' => ['2026-10-10 04:59:00', Theme::Night],
    '5:00 は朝' => ['2026-10-10 05:00:00', Theme::Morning],
    '9:59 は朝' => ['2026-10-10 09:59:00', Theme::Morning],
    '10:00 は昼' => ['2026-10-10 10:00:00', Theme::Day],
    '15:59 は昼' => ['2026-10-10 15:59:00', Theme::Day],
    '16:00 は夕' => ['2026-10-10 16:00:00', Theme::Evening],
    '18:59 は夕' => ['2026-10-10 18:59:00', Theme::Evening],
    '19:00 は夜' => ['2026-10-10 19:00:00', Theme::Night],
    '0:00 は夜' => ['2026-10-10 00:00:00', Theme::Night],
]);

it('季節の境目の日を判定できる', function (string $date, Season $expected): void {
    expect((new ThemeResolver)->seasonAt(jst($date.' 12:00:00')))->toBe($expected);
})->with([
    '2月末は冬' => ['2026-02-28', Season::Winter],
    '3月1日は春' => ['2026-03-01', Season::Spring],
    '5月31日は春' => ['2026-05-31', Season::Spring],
    '6月1日は夏' => ['2026-06-01', Season::Summer],
    '8月31日は夏' => ['2026-08-31', Season::Summer],
    '9月1日は秋' => ['2026-09-01', Season::Autumn],
    '11月30日は秋' => ['2026-11-30', Season::Autumn],
    '12月1日は冬' => ['2026-12-01', Season::Winter],
    '1月1日は冬' => ['2027-01-01', Season::Winter],
]);

it('UTC の時刻でも日本時間で判定する', function (): void {
    // UTC 0:59 は日本時間 9:59(朝)、UTC 1:00 は日本時間 10:00(昼)
    $resolver = new ThemeResolver;

    expect($resolver->themeAt(CarbonImmutable::parse('2026-10-10 00:59:00', 'UTC')))->toBe(Theme::Morning)
        ->and($resolver->themeAt(CarbonImmutable::parse('2026-10-10 01:00:00', 'UTC')))->toBe(Theme::Day);
});

it('日本時間ではもう翌月のとき、季節も日本時間で判定する', function (): void {
    // UTC 2026-05-31 15:00 は日本時間 2026-06-01 0:00(夏)
    expect((new ThemeResolver)->seasonAt(CarbonImmutable::parse('2026-05-31 15:00:00', 'UTC')))->toBe(Season::Summer);
});

it('昼固定・夜固定を選ぶと時刻に関係なくその配色になる', function (string $at): void {
    $resolver = new ThemeResolver;

    expect($resolver->resolve(ThemePreference::Day, jst($at)))->toBe(Theme::Day)
        ->and($resolver->resolve(ThemePreference::Night, jst($at)))->toBe(Theme::Night);
})->with(['2026-10-10 03:00:00', '2026-10-10 12:00:00', '2026-10-10 17:30:00', '2026-10-10 23:59:00']);

it('自動なら時刻で決まる', function (): void {
    expect((new ThemeResolver)->resolve(ThemePreference::Auto, jst('2026-10-10 17:00:00')))->toBe(Theme::Evening);
});

it('Cookie の値が不正なら自動にする', function (): void {
    expect(ThemePreference::fromCookie(null))->toBe(ThemePreference::Auto)
        ->and(ThemePreference::fromCookie('rainbow'))->toBe(ThemePreference::Auto)
        ->and(ThemePreference::fromCookie('night'))->toBe(ThemePreference::Night);
});
