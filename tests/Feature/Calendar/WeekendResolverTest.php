<?php

declare(strict_types=1);

use App\Services\Calendar\WeekendResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** 2026年の祝日(振替休日を含む)の一部 */
function seedHolidays2026(): void
{
    $rows = [
        '2026-04-29' => '昭和の日', '2026-05-03' => '憲法記念日', '2026-05-04' => 'みどりの日', '2026-05-05' => 'こどもの日', '2026-05-06' => '休日(憲法記念日)',
        '2026-07-20' => '海の日', '2026-09-21' => '敬老の日', '2026-09-22' => '休日', '2026-09-23' => '秋分の日', '2026-10-12' => 'スポーツの日',
        '2026-11-03' => '文化の日', '2026-11-23' => '勤労感謝の日',
    ];
    foreach ($rows as $date => $name) {
        DB::table('holidays')->insert(['date' => $date, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
    }
}

function weekendAt(string $jst): array
{
    $r = (new WeekendResolver)->resolve(CarbonImmutable::parse($jst, 'Asia/Tokyo'));

    return [$r['from']->toDateString(), $r['to']->toDateString(), $r['friday_night']?->toDateString()];
}

beforeEach(function (): void {
    seedHolidays2026();
});

it('ふつうの週は、次の土曜と日曜。前の金曜の夜も含める', function (): void {
    // 2026-10-21 は水曜、10-19 は月曜(祝日ではない週)
    expect(weekendAt('2026-10-21 10:00'))->toBe(['2026-10-24', '2026-10-25', '2026-10-23'])
        ->and(weekendAt('2026-10-19 09:00'))->toBe(['2026-10-24', '2026-10-25', '2026-10-23'])
        ->and(weekendAt('2026-10-14 09:00')) // 10-10〜12 は連休だったが、水曜からは次の週末
        ->toBe(['2026-10-17', '2026-10-18', '2026-10-16']);
});

it('月曜が祝日(スポーツの日)なら、土〜月の連休になる', function (): void {
    // 連休はスポーツの日(10/12)まで
    $r = (new WeekendResolver)->resolve(CarbonImmutable::parse('2026-10-08 12:00', 'Asia/Tokyo'));
    expect($r['from']->toDateString())->toBe('2026-10-10')->and($r['to']->toDateString())->toBe('2026-10-12');
});

it('金曜が祝日なら、金〜日。金曜は連休に入るので、金曜の夜の特例は使わない', function (): void {
    DB::table('holidays')->insert(['date' => '2026-10-23', 'name' => '架空の金曜の祝日', 'created_at' => now(), 'updated_at' => now()]);

    expect(weekendAt('2026-10-20 10:00'))->toBe(['2026-10-23', '2026-10-25', null]);
});

it('ゴールデンウィークは、土曜から振替休日(水曜)までつながる。連休の途中でも同じ連休を返す', function (): void {
    expect(weekendAt('2026-04-30 10:00'))->toBe(['2026-05-02', '2026-05-06', '2026-05-01'])
        ->and(weekendAt('2026-05-02 08:00'))->toBe(['2026-05-02', '2026-05-06', '2026-05-01'])
        ->and(weekendAt('2026-05-04 20:00'))->toBe(['2026-05-02', '2026-05-06', '2026-05-01']);
});

it('平日だけの祝日(水曜1日)は週末として扱わない', function (): void {
    // 2026-07-20 の海の日は月曜。つながって 7/18〜7/20 の連休
    expect(weekendAt('2026-07-15 10:00'))->toBe(['2026-07-18', '2026-07-20', '2026-07-17']);

    // 祝日が水曜1日だけのとき(昭和の日 4/29 は水曜)。4/27(月)の「今週末」は 5/2 からの連休で、4/29 ではない
    expect(weekendAt('2026-04-27 10:00')[0])->toBe('2026-05-02');
});

it('いまが土曜・日曜・連休の中なら、いまを含む連休。日曜の23:59はまだ今週末で、月曜の0:00から次の週末になる', function (): void {
    expect(weekendAt('2026-10-17 07:00'))->toBe(['2026-10-17', '2026-10-18', '2026-10-16'])
        ->and(weekendAt('2026-10-18 23:59'))->toBe(['2026-10-17', '2026-10-18', '2026-10-16'])
        ->and(weekendAt('2026-10-19 00:00'))->toBe(['2026-10-24', '2026-10-25', '2026-10-23']);
});

it('金曜の夜も、次の土曜からの週末に数える(金曜の昼も同じ範囲を返す。夜の判定は日程の開始時刻で行う)', function (): void {
    expect(weekendAt('2026-10-16 19:00'))->toBe(['2026-10-17', '2026-10-18', '2026-10-16'])
        ->and(weekendAt('2026-10-16 08:00'))->toBe(['2026-10-17', '2026-10-18', '2026-10-16']);
});

it('日本時間で判定する(UTC の時刻でも)', function (): void {
    $before = (new WeekendResolver)->resolve(CarbonImmutable::parse('2026-10-18 14:59:00', 'UTC'));
    $after = (new WeekendResolver)->resolve(CarbonImmutable::parse('2026-10-18 15:00:00', 'UTC'));

    // UTC 14:59 は日本時間の日曜 23:59、UTC 15:00 は日本時間の月曜 0:00
    expect($before['from']->toDateString())->toBe('2026-10-17')
        ->and($after['from']->toDateString())->toBe('2026-10-24');
});
