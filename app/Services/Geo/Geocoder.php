<?php

declare(strict_types=1);

namespace App\Services\Geo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 住所・会場名から、緯度・経度を調べる(国土地理院の住所検索 API。無料・キー不要)。
 * 巡回で取り込んだ行事に、地図のピンの位置を入れるために使う。外れそうな結果は受け入れない:
 *  - 結果の名前に、調べた文字がそのまま含まれる(または、調べた文字が結果の名前を含む。名前が3文字以上)ものだけ。
 *    「香川県高松市」のような、市の中心だけを返す大ざっぱな結果は、会場の位置としては使わない
 *  - 近く($near)が分かっているときは、その近く(既定は緯度・経度で 0.6 度以内)のものだけ
 * 結果は30日、覚えておく(同じ会場を何度も調べない)。
 */
final class Geocoder
{
    public const URL = 'https://msearch.gsi.go.jp/address-search/AddressSearch';

    /**
     * @param  array{0: float, 1: float}|null  $near  [緯度, 経度]。分かっていれば、その近くの結果だけ受け入れる
     * @return array{lat: float, lng: float}|null
     */
    public function find(string $query, ?string $prefectureName = null, ?array $near = null): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }
        $key = 'geocode:'.sha1($query.'|'.($prefectureName ?? '').'|'.json_encode($near));

        return Cache::remember($key, now()->addDays(30), function () use ($query, $prefectureName, $near): ?array {
            // 会場名は、そのままの検索がいちばん正確(「高松市美術館」)。なければ、県の名前を前に付けて検索し直す(住所)
            $found = $this->request($query, $query, $near);
            if ($found === null && $prefectureName !== null && ! str_contains($query, $prefectureName)) {
                $found = $this->request($prefectureName.$query, $query, $near);
            }

            return $found;
        });
    }

    /**
     * @param  array{0: float, 1: float}|null  $near
     * @return array{lat: float, lng: float}|null
     */
    private function request(string $q, string $original, ?array $near): ?array
    {
        try {
            $response = Http::timeout(10)->withUserAgent('DoInaka/1.0 (+https://xn--gdkt37rmci.net/)')->acceptJson()->get(self::URL, ['q' => $q]);
        } catch (Throwable) {
            return null;
        }
        if (! $response->successful() || ! is_array($response->json())) {
            return null;
        }

        foreach ((array) $response->json() as $row) {
            if (! is_array($row)) {
                continue;
            }
            $geometry = is_array($row['geometry'] ?? null) ? $row['geometry'] : [];
            $properties = is_array($row['properties'] ?? null) ? $row['properties'] : [];
            $coords = $geometry['coordinates'] ?? null;
            $title = is_string($properties['title'] ?? null) ? $properties['title'] : '';
            if (! is_array($coords) || ! is_numeric($coords[0] ?? null) || ! is_numeric($coords[1] ?? null)) {
                continue;
            }
            [$lng, $lat] = [(float) $coords[0], (float) $coords[1]];
            // 日本の範囲外は捨てる
            if ($lat < 20 || $lat > 46 || $lng < 122 || $lng > 154) {
                continue;
            }
            if ($near !== null && (abs($lat - $near[0]) > 0.6 || abs($lng - $near[1]) > 0.6)) {
                continue;
            }
            if ($this->matches($original, $title)) {
                return ['lat' => round($lat, 6), 'lng' => round($lng, 6)];
            }
        }

        return null;
    }

    /** 結果の名前が、調べた文字と(ほぼ)同じものか */
    private function matches(string $original, string $title): bool
    {
        $a = (string) preg_replace('/\s+/u', '', $original);
        $b = (string) preg_replace('/\s+/u', '', $title);
        if ($a === '' || $b === '') {
            return false;
        }

        return str_contains($b, $a) || (mb_strlen($b) >= 3 && str_contains($a, $b) && mb_strlen($b) >= mb_strlen($a) / 2);
    }
}
