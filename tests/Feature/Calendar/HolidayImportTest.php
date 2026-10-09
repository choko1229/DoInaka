<?php

declare(strict_types=1);

use App\Services\Calendar\HolidayImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** 内閣府の CSV と同じ形(Shift_JIS、ヘッダー行つき、年月日は「2026/1/1」)を作る */
function cabinetCsv(array $rows): string
{
    $lines = ['国民の祝日・休日月日,国民の祝日・休日名称'];
    foreach ($rows as $date => $name) {
        $lines[] = "{$date},{$name}";
    }

    return mb_convert_encoding(implode("\r\n", $lines)."\r\n", 'SJIS-win', 'UTF-8');
}

it('祝日の CSV(Shift_JIS)を正しく読める', function (): void {
    $parsed = (new HolidayImporter)->parse(cabinetCsv([
        '2026/1/1' => '元日', '2026/5/6' => '休日(憲法記念日)', '2026/10/12' => 'スポーツの日', '2026/11/23' => '勤労感謝の日',
    ]));

    expect($parsed)->toBe(['2026-01-01' => '元日', '2026-05-06' => '休日(憲法記念日)', '2026-10-12' => 'スポーツの日', '2026-11-23' => '勤労感謝の日']);
});

it('ヘッダー行・空行・存在しない日付は読み飛ばす', function (): void {
    $parsed = (new HolidayImporter)->parse(cabinetCsv(['2026/2/30' => '架空', '2026/1/1' => '元日']));

    expect($parsed)->toBe(['2026-01-01' => '元日']);
});

it('取り込むと holidays に入り、もう一度取り込んでも二重にならない(名前の変更は反映する)', function (): void {
    Http::fake([HolidayImporter::URL => Http::sequence()
        ->push(cabinetCsv(['2026/1/1' => '元日', '2026/2/11' => '建国記念の日']))
        ->push(cabinetCsv(['2026/1/1' => '元日(改)', '2026/2/11' => '建国記念の日']))]);

    expect((new HolidayImporter)->import())->toBe(2)->and(DB::table('holidays')->count())->toBe(2);

    (new HolidayImporter)->import();

    expect(DB::table('holidays')->count())->toBe(2)->and(DB::table('holidays')->where('date', '2026-01-01')->value('name'))->toBe('元日(改)');
});

it('取得に失敗したときは、いまの内容をそのまま使う', function (): void {
    DB::table('holidays')->insert(['date' => '2026-01-01', 'name' => '元日', 'created_at' => now(), 'updated_at' => now()]);
    Http::fake([HolidayImporter::URL => Http::sequence()->pushStatus(500)->push('<html>メンテナンス中</html>')]);

    expect(fn () => (new HolidayImporter)->import())->toThrow(RuntimeException::class);
    expect(DB::table('holidays')->count())->toBe(1);

    // 読み取れる行が1つもない CSV(壊れたデータ)も、いまの内容を消さない
    expect(fn () => (new HolidayImporter)->import())->toThrow(RuntimeException::class);
    expect(DB::table('holidays')->count())->toBe(1);
});

it('holidays:import コマンドは、失敗しても例外を投げず、失敗として終わる', function (): void {
    Http::fake([HolidayImporter::URL => Http::sequence()->pushStatus(503)->pushStatus(503)->push(cabinetCsv(['2026/1/1' => '元日']))]);

    $this->artisan('holidays:import')->assertFailed();
    $this->artisan('holidays:import')->assertSuccessful();
});
