<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Region;
use App\Models\Spot;
use Illuminate\Support\Carbon;

/*
 * 地図(画面デザイン MapPC・Map): 左に一覧と絞り込み、右に地図。ピンの情報はサーバーが JSON で渡す。
 */
it('地図の画面: 絞り込みの印(イベント・スポット・今週末)と、ピンの JSON(種類・見出し・地域)', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-14 10:00:00', 'Asia/Tokyo'));
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'marugame', 'name' => '丸亀市']);
    Spot::factory()->create(['title' => '見晴らし岩', 'region_id' => $city->id, 'is_published' => true, 'lat' => 34.3, 'lng' => 134.0]);
    Event::factory()->published()->onDate('2026-10-17')->create(['title' => '秋祭り', 'region_id' => $city->id, 'lat' => 34.31, 'lng' => 134.01]);

    $response = $this->get('/kagawa/map/')->assertOk()
        ->assertSee('data-map-app', false)->assertSee('この範囲で探す')->assertSee('data-weekend', false)->assertSee('夜道は暗いです');
    $html = $response->getContent();
    preg_match("/data-pins='([^']*)'/", (string) $html, $m);
    $pins = json_decode(html_entity_decode($m[1]), true);

    expect($pins)->toHaveCount(2)
        ->and(collect($pins)->firstWhere('type', 'spot')['area'])->toBe('丸亀市')
        ->and(collect($pins)->firstWhere('type', 'event')['weekend'])->toBeTrue();
    Carbon::setTestNow();
});
