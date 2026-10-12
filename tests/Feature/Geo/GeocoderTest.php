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
function gsiReply(array ...$rows): array
{
    return array_map(fn (array $r): array => ['geometry' => ['type' => 'Point', 'coordinates' => [$r['lng'], $r['lat']]], 'type' => 'Feature', 'properties' => ['title' => $r['title']]], $rows);
}

beforeEach(function (): void {
    Cache::flush();
});

it('住所・会場名から位置を調べ、県の名前を前に付けて検索する。同じ検索は覚えている', function (): void {
    Http::fake([Geocoder::URL.'*' => Http::response(gsiReply(['lat' => 34.3446, 'lng' => 134.0453, 'title' => '香川県高松市紺屋町']))]);

    $geocoder = app(Geocoder::class);
    expect($geocoder->find('高松市美術館', '香川県'))->toBe(['lat' => 34.3446, 'lng' => 134.0453]);
    $geocoder->find('高松市美術館', '香川県');

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $r): bool => ($r->data()['q'] ?? '') === '香川県高松市美術館');
});

it('範囲外・名前が重ならない結果・失敗は、位置にしない', function (): void {
    Http::fake([Geocoder::URL.'*' => Http::sequence()
        ->push(gsiReply(['lat' => 10.0, 'lng' => 100.0, 'title' => '香川県高松市']))
        ->push(gsiReply(['lat' => 34.3, 'lng' => 134.0, 'title' => '東京都千代田区']))
        ->push('error', 500)]);

    $geocoder = app(Geocoder::class);
    expect($geocoder->find('高松市', '香川県'))->toBeNull()
        ->and($geocoder->find('丸亀城', '香川県'))->toBeNull()
        ->and($geocoder->find('観音寺市役所', '香川県'))->toBeNull()
        ->and($geocoder->find('  ', '香川県'))->toBeNull();
});

it('events:geocode: 位置のない公開中の行事にだけ、位置を入れる', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'takamatsu', 'name' => '高松市']);
    // 公開には情報元が1件要る。下書きで作り、情報元を付けて公開する
    $publish = function (array $attributes) use ($city): Event {
        $event = Event::factory()->create($attributes + ['region_id' => $city->id, 'is_published' => false]);
        $event->sources()->create(['kind' => 'url', 'url' => 'https://example.com/'.$event->id, 'title' => '情報元']);

        return $event;
    };
    $withoutPlace = $publish(['venue_name' => '高松市美術館', 'address' => null, 'lat' => null, 'lng' => null]);
    $withoutPlace->forceFill(['is_published' => true])->save();
    $withPlace = $publish(['venue_name' => '栗林公園', 'lat' => 34.33, 'lng' => 134.04]);
    $withPlace->forceFill(['is_published' => true])->save();
    $draft = $publish(['venue_name' => '下書きの会場', 'lat' => null, 'lng' => null]);
    Http::fake([Geocoder::URL.'*' => Http::response(gsiReply(['lat' => 34.3446, 'lng' => 134.0453, 'title' => '香川県高松市美術館']))]);

    $this->artisan('events:geocode')->expectsOutputToContain('1 件に位置を入れました')->assertSuccessful();

    expect((float) $withoutPlace->refresh()->lat)->toBe(34.3446)->and((float) $withPlace->refresh()->lat)->toBe(34.33)->and($draft->refresh()->lat)->toBeNull();
});
