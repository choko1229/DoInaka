<?php

declare(strict_types=1);

use App\Models\Region;
use App\Models\RegionStat;

/*
 * 地域ページ(画面デザイン RegionPC・RegionSP): 基本情報(読み・人口・面積・合併)、紹介文、出典。人口・面積は CSV の取り込みで入る(F-P06)。
 */
it('基本情報: 読みと、取り込んだ人口・面積。取り込み前は「あとで表示」と案内する', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'marugame', 'name' => '丸亀市', 'name_kana' => 'まるがめし', 'code' => '372021']);

    $this->get('/kagawa/marugame/')->assertOk()->assertSee('基本情報')->assertSee('まるがめし')->assertSee('統計の取り込みが終わると表示されます');

    RegionStat::query()->create(['region_id' => $city->id, 'population' => 109000, 'population_year' => 2020, 'area_km2' => '111.78', 'source_label' => '国勢調査']);
    $this->get('/kagawa/marugame/')->assertOk()->assertSee('109,000人')->assertSee('2020年')->assertSee('111.78 km²')->assertSee('数字は統計から自動で入れています');
});

it('合併: 合併前の町があれば、日付と町の名前を並べる。出典は番号つきで並ぶ', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'marugame', 'name' => '丸亀市']);
    Region::factory()->create(['parent_id' => $city->id, 'former_parent_id' => $city->id, 'slug' => 'ayauta', 'name' => '綾歌町', 'abolished_on' => '2005-03-22']);
    $city->forceFill(['intro_body' => '丸亀市は香川県の市です。', 'intro_sources' => [['n' => 1, 'title' => '市の概要', 'url' => 'https://example.com/a'], ['n' => 2, 'title' => '沿革', 'url' => 'https://example.com/b']], 'intro_fact_checked' => true, 'intro_generated_at' => now()])->save();

    $this->get('/kagawa/marugame/')->assertOk()->assertSee('2005年3月22日')->assertSee('丸亀市・綾歌町')->assertSee('丸亀市は香川県の市です。')->assertSee('出典')->assertSee('市の概要')->assertSee('id="ref-2"', false);
});

it('人口・面積の取り込み: コードが合う地域だけ入り、再実行しても増えない', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'marugame', 'code' => '372021']);
    $file = tempnam(sys_get_temp_dir(), 'stats');
    file_put_contents($file, "code,population,population_year,area_km2,source_label,source_url\n372021,109000,2020,111.78,国勢調査,https://www.stat.go.jp/\n999999,1,2020,1,x,https://example.com/\n");

    $this->artisan('regions:import-stats', ['file' => $file])->expectsOutputToContain('1 件取り込みました')->assertSuccessful();
    $this->artisan('regions:import-stats', ['file' => $file])->assertSuccessful();

    expect(RegionStat::query()->count())->toBe(1)->and(RegionStat::query()->where('region_id', $city->id)->first()->population)->toBe(109000);
    unlink($file);
});
