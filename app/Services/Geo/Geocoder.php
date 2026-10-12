<?php

declare(strict_types=1);

namespace App\Services\Geo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 住所・会場名から、緯度・経度を調べる(国土地理院の住所検索 API。無料・キー不要)。
 * 巡回で取り込んだ行事に、地図のピンの位置を入れるために使う。外れそうな結果は受け入れない:
 * 日本の範囲外、または、調べた文字に地名が1つも重ならないものは捨てる。結果は30日、覚えておく(同じ会場を何度も調べない)。
 */
final class Geocoder
{
    public const URL = 'https://msearch.gsi.go.jp/address-search/AddressSearch';

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function find(string $query, ?string $prefectureName = null): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }
        $q = ($prefectureName !== null && ! str_contains($query, $prefectureName) ? $prefectureName : '').$query;

        return Cache::remember('geocode:'.sha1($q), now()->addDays(30), fn (): ?array => $this->request($q, $query));
    }

    /** @return array{lat: float, lng: float}|null */
    private function request(string $q, string $original): ?array
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
            // 調べた文字の一部(2文字以上)が、結果の名前に含まれているものだけ受け入れる
            if ($title !== '' && $this->overlaps($original, $title)) {
                return ['lat' => round($lat, 6), 'lng' => round($lng, 6)];
            }
        }

        return null;
    }

    private function overlaps(string $a, string $b): bool
    {
        $a = (string) preg_replace('/\s+/u', '', $a);
        $length = mb_strlen($a);
        for ($i = 0; $i + 2 <= $length; $i++) {
            if (str_contains($b, mb_substr($a, $i, 2))) {
                return true;
            }
        }

        return false;
    }
}
