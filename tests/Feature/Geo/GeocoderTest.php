<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Region;
use App\Services\Geo\Geocoder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/*
 * 住所・会場名 → 緯度・経度(国土地理院の住所検索)。外への通信は、テストでは必ずモックする。
 */
function gsiRow(float $lat, float $lng, string $title): array
{
    return ['type' => 'Feature', 'geometry' => ['type' => 'Point', 'coordinates' => [$lng, $lat]], 'properties' => ['title' => $title]];
}

/** 検索語ごとに返事を決める(語 => 結果の行のリスト)。ないものは空 */
function fakeGsi(array $byQuery): void
{
    Http::fake([Geocoder::URL.'*' => fn (Request $request) => Http::response($byQuery[$request->data()['q'] ?? ''] ?? [])]);
}

beforeEach(function (): void {
    Cache::flush();
});

it('会場名は、そのまま検索して、名前がそのまま合う結果を使う(市の中心だけの大ざっぱな結果は使わない)。同じ検索は覚えている', function (): void {
    fakeGsi(['高松市美術館' => [gsiRow(34.3427, 134.0465, '香川県高松市'), gsiRow(34.3441, 134.0491, '高松市美術館')]]);

    $geocoder = app(Geocoder::class);
    expect($geocoder->find('高松市美術館', '香川県'))->toBe(['lat' => 34.3441, 'lng' => 134.0491]);
    $geocoder->find('高松市美術館', '香川県');

    Http::assertSentCount(1);
});

it('住所は、そのままで見つからなければ、県の名前を前に付けて検索し直す', function (): void {
    fakeGsi(['香川県丸亀市一番丁' => [gsiRow(34.2899, 133.7977, '香川県丸亀市一番丁')]]);

    expect(app(Geocoder::class)->find('丸亀市一番丁', '香川県'))->toBe(['lat' => 34.2899, 'lng' => 133.7977]);
    Http::assertSentCount(2);
});

it('範囲外・名前が合わない・遠い・失敗は、位置にしない', function (): void {
    fakeGsi([
        '範囲外の会場' => [gsiRow(10.0, 100.0, '範囲外の会場')],
        '丸亀城' => [gsiRow(34.3, 134.0, '東京都千代田区')],
        '遠い会場' => [gsiRow(43.0, 141.3, '遠い会場')],
    ]);

    $geocoder = app(Geocoder::class);
    expect($geocoder->find('範囲外の会場'))->toBeNull()
        ->and($geocoder->find('丸亀城', '香川県'))->toBeNull()
        ->and($geocoder->find('遠い会場', '香川県', [34.3, 134.0]))->toBeNull()
        ->and($geocoder->find('  ', '香川県'))->toBeNull();

    Http::fake([Geocoder::URL.'*' => Http::response('error', 500)]);
    expect($geocoder->find('観音寺市役所', '香川県'))->toBeNull();
});

it('events:geocode: 位置のない公開中の行事にだけ、位置を入れる', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'takamatsu', 'name' => '高松市']);
    // 公開には情報元が1件要る。下書きで作り、情報元を付けて公開する
    $make = function (array $attributes) use ($city): Event {
        $event = Event::factory()->create($attributes + ['region_id' => $city->id, 'is_published' => false]);
        $event->sources()->create(['kind' => 'url', 'url' => 'https://example.com/'.$event->id, 'title' => '情報元']);

        return $event;
    };
    $withoutPlace = $make(['venue_name' => '高松市美術館', 'address' => null, 'lat' => null, 'lng' => null]);
    $withoutPlace->forceFill(['is_published' => true])->save();
    $withPlace = $make(['venue_name' => '栗林公園', 'lat' => 34.33, 'lng' => 134.04]);
    $withPlace->forceFill(['is_published' => true])->save();
    $draft = $make(['venue_name' => '下書きの会場', 'lat' => null, 'lng' => null]);
    fakeGsi(['高松市美術館' => [gsiRow(34.3441, 134.0491, '高松市美術館')]]);

    $this->artisan('events:geocode')->expectsOutputToContain('1 件に位置を入れました')->assertSuccessful();

    expect((float) $withoutPlace->refresh()->lat)->toBe(34.3441)->and((float) $withPlace->refresh()->lat)->toBe(34.33)->and($draft->refresh()->lat)->toBeNull();
});
