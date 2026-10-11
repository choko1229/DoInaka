<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Region;
use Illuminate\Console\Command;

/**
 * 国土数値情報(行政区域データ N03 の GeoJSON)から、地域ページの「形の図」に使う、軽い輪郭のデータを作る(開発者が手元で動かす)。
 * 元のデータ(数MB〜)はコミットしない。作った軽いデータ(resources/data/outlines/{都道府県コード}.json)だけをコミットする。
 *
 * 使い方: php artisan regions:build-outlines {N03 の geojson} {都道府県コード(例 37)}
 * 出力: { "37201": [ [[lng,lat],...], ... ], ..., "37000": 県全体 }(市区町村コード5桁。リングは外周だけ)
 */
final class RegionsBuildOutlines extends Command
{
    protected $signature = 'regions:build-outlines {geojson : 国土数値情報 N03 の GeoJSON のパス} {pref : 都道府県コード(2桁)} {--former= : 合併前の年の版(N03 の XML。2000年版など)のパス。合併前の町の輪郭(点線)を作る}';

    protected $description = '国土数値情報の行政区域から、地域ページの形の図に使う軽い輪郭のデータを作る';

    public function handle(): int
    {
        $path = (string) $this->argument('geojson');
        $pref = str_pad((string) $this->argument('pref'), 2, '0', STR_PAD_LEFT);

        $json = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        if (! is_array($json) || ! is_array($json['features'] ?? null)) {
            $this->error('GeoJSON を読めませんでした。');

            return self::FAILURE;
        }

        /** @var array<string, list<list<array{0: float, 1: float}>>> $byCity */
        $byCity = [];
        foreach ($json['features'] as $feature) {
            if (! is_array($feature) || ! is_array($feature['geometry'] ?? null) || ! is_array($feature['properties'] ?? null)) {
                continue;
            }
            $code = $feature['properties']['N03_007'] ?? null;
            if (! is_string($code) || ! str_starts_with($code, $pref)) {
                continue;
            }
            foreach ($this->outerRings($feature['geometry']) as $ring) {
                $byCity[$code][] = $ring;
            }
        }
        if ($byCity === []) {
            $this->error('都道府県コード '.$pref.' の市区町村がありません。');

            return self::FAILURE;
        }

        $all = array_merge(...array_values($byCity));
        $extent = $this->extent($all);
        // 県全体は粗く(幅の 1/300)、市町は細かく(その市町の幅の 1/200)簡略化する
        $out = [$pref.'000' => $this->prepare($all, max($extent[1] - $extent[0], $extent[3] - $extent[2]) / 300)];
        foreach ($byCity as $code => $rings) {
            $e = $this->extent($rings);
            $out[$code] = $this->prepare($rings, max($e[1] - $e[0], $e[3] - $e[2]) / 200);
        }

        $formerPath = $this->option('former');
        if (is_string($formerPath) && $formerPath !== '') {
            $former = $this->formerOutlines($formerPath, $pref);
            if ($former === null) {
                $this->error('合併前の版の XML を読めませんでした。');

                return self::FAILURE;
            }
            // 地域(合併前の町)に同じ名前があるものだけ残す
            $names = [];
            foreach (Region::query()->whereNotNull('abolished_on')->pluck('name') as $name) {
                if (is_string($name)) {
                    $names[$name] = true;
                }
            }
            $out['former'] = array_intersect_key($former, $names);
        }

        $dir = resource_path('data/outlines');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $dir.'/'.$pref.'.json';
        file_put_contents($file, json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

        $this->info(count($out).' 件を書きました: '.$file.'('.round((int) filesize($file) / 1024, 1).' KB)');

        return self::SUCCESS;
    }

    /**
     * 合併前の年の版(N03 の XML。曲線 Curve と、向きを逆にする OrientableCurve と、面 Surface をつなぐ形式)から、
     * 町ごとの外周のリングを作る(簡略化済み)。キーは町の名前。
     *
     * @return array<string, list<list<array{0: float, 1: float}>>>|null
     */
    private function formerOutlines(string $path, string $pref): ?array
    {
        $xml = is_file($path) ? (string) file_get_contents($path) : '';
        if ($xml === '') {
            return null;
        }

        /** @var array<string, list<array{0: float, 1: float}>> $curves */
        $curves = [];
        if (preg_match_all('/<gml:Curve gml:id="(c\d+)">.*?<gml:posList>(.*?)<\/gml:posList>/s', $xml, $m, PREG_SET_ORDER) > 0) {
            foreach ($m as $row) {
                $numbers = preg_split('/\s+/', trim($row[2])) ?: [];
                $points = [];
                for ($i = 0; $i + 1 < count($numbers); $i += 2) {
                    // posList は「緯度 経度」の順
                    $points[] = [(float) $numbers[$i + 1], (float) $numbers[$i]];
                }
                $curves[$row[1]] = $points;
            }
        }

        /** @var array<string, array{0: string, 1: bool}> $oriented 向きつきの曲線: id => [元の曲線, 逆向きか] */
        $oriented = [];
        if (preg_match_all('/<gml:OrientableCurve gml:id="(_c\d+)" orientation="([+-])">\s*<gml:baseCurve xlink:href="#(c\d+)"\/>/s', $xml, $m, PREG_SET_ORDER) > 0) {
            foreach ($m as $row) {
                $oriented[$row[1]] = [$row[3], $row[2] === '-'];
            }
        }

        /** @var array<string, list<array{0: float, 1: float}>> $surfaces */
        $surfaces = [];
        if (preg_match_all('/<gml:Surface gml:id="(a\d+)">(.*?)<\/gml:Surface>/s', $xml, $m, PREG_SET_ORDER) > 0) {
            foreach ($m as $row) {
                $ring = [];
                preg_match_all('/xlink:href="#(_?c\d+)"/', $row[2], $members);
                foreach ($members[1] as $id) {
                    [$base, $reverse] = str_starts_with($id, '_') ? ($oriented[$id] ?? [null, false]) : [$id, false];
                    $points = $base !== null ? ($curves[$base] ?? []) : [];
                    if ($reverse) {
                        $points = array_reverse($points);
                    }
                    // つなぎ目の点が重なるときは、片方を省く
                    if ($ring !== [] && $points !== [] && $ring[count($ring) - 1] === $points[0]) {
                        array_shift($points);
                    }
                    array_push($ring, ...$points);
                }
                if (count($ring) >= 3) {
                    if ($ring[0] !== $ring[count($ring) - 1]) {
                        $ring[] = $ring[0];
                    }
                    $surfaces[$row[1]] = $ring;
                }
            }
        }

        $result = [];
        if (preg_match_all('/<ksj:AdministrativeBoundary .*?<ksj:bounds xlink:href="#(a\d+)"\/>.*?<ksj:cityName>(.*?)<\/ksj:cityName>.*?<ksj:administrativeAreaCode[^>]*>(\d+)</s', $xml, $m, PREG_SET_ORDER) > 0) {
            foreach ($m as $row) {
                if (! str_starts_with($row[3], $pref) || ! isset($surfaces[$row[1]])) {
                    continue;
                }
                $result[$row[2]][] = $surfaces[$row[1]];
            }
        }

        // 町ごとに、簡略化する(町の幅の 1/100。点線なので粗くてよい)
        return array_map(fn (array $rings): array => $this->prepare($rings, $this->tolerance($rings, 100)), $result);
    }

    /**
     * @param  list<list<array{0: float, 1: float}>>  $rings
     */
    private function tolerance(array $rings, int $divisor): float
    {
        $e = $this->extent($rings);

        return max($e[1] - $e[0], $e[3] - $e[2]) / $divisor;
    }

    /**
     * ジオメトリの外周のリング(穴は使わない)。
     *
     * @param  array<mixed>  $geometry
     * @return list<list<array{0: float, 1: float}>>
     */
    private function outerRings(array $geometry): array
    {
        $coords = $geometry['coordinates'] ?? null;
        $polygons = match ($geometry['type'] ?? null) {
            'Polygon' => [$coords],
            'MultiPolygon' => $coords,
            default => [],
        };

        $rings = [];
        foreach (is_array($polygons) ? $polygons : [] as $polygon) {
            $outer = is_array($polygon) ? ($polygon[0] ?? null) : null;
            if (! is_array($outer)) {
                continue;
            }
            $ring = [];
            foreach ($outer as $point) {
                if (is_array($point) && is_numeric($point[0] ?? null) && is_numeric($point[1] ?? null)) {
                    $ring[] = [(float) $point[0], (float) $point[1]];
                }
            }
            if (count($ring) >= 4) {
                $rings[] = $ring;
            }
        }

        return $rings;
    }

    /**
     * 小さすぎるリング(ごく小さな島)を除き、簡略化して、小数点以下を丸める。
     *
     * @param  list<list<array{0: float, 1: float}>>  $rings
     * @return list<list<array{0: float, 1: float}>>
     */
    private function prepare(array $rings, float $tolerance): array
    {
        $areas = array_map(fn (array $r): float => abs($this->area($r)), $rings);
        if ($areas === []) {
            return [];
        }
        $largest = max($areas);

        $result = [];
        foreach ($rings as $i => $ring) {
            // いちばん大きな面積の 1/500 より小さい島は、図には出さない
            if ($areas[$i] < $largest / 500) {
                continue;
            }
            $simple = $this->simplify($ring, $tolerance);
            if (count($simple) >= 4) {
                $result[] = array_map(fn (array $p): array => [round($p[0], 4), round($p[1], 4)], $simple);
            }
        }

        return $result;
    }

    /**
     * @param  list<list<array{0: float, 1: float}>>  $rings
     * @return array{0: float, 1: float, 2: float, 3: float} [minX, maxX, minY, maxY]
     */
    private function extent(array $rings): array
    {
        $xs = [];
        $ys = [];
        foreach ($rings as $ring) {
            foreach ($ring as [$x, $y]) {
                $xs[] = $x;
                $ys[] = $y;
            }
        }

        if ($xs === [] || $ys === []) {
            return [0.0, 0.0, 0.0, 0.0];
        }

        return [min($xs), max($xs), min($ys), max($ys)];
    }

    /** @param  list<array{0: float, 1: float}>  $ring */
    private function area(array $ring): float
    {
        $sum = 0.0;
        $n = count($ring);
        for ($i = 0; $i < $n - 1; $i++) {
            $sum += $ring[$i][0] * $ring[$i + 1][1] - $ring[$i + 1][0] * $ring[$i][1];
        }

        return $sum / 2;
    }

    /**
     * Douglas-Peucker(繰り返しで書く。点が多くても深くならない)。
     *
     * @param  list<array{0: float, 1: float}>  $points
     * @return list<array{0: float, 1: float}>
     */
    private function simplify(array $points, float $tolerance): array
    {
        $n = count($points);
        $keep = array_fill(0, $n, false);
        $keep[0] = $keep[$n - 1] = true;
        $stack = [[0, $n - 1]];

        while ($stack !== []) {
            [$start, $end] = array_pop($stack);
            $maxDist = 0.0;
            $index = -1;
            for ($i = $start + 1; $i < $end; $i++) {
                $d = $this->distance($points[$i], $points[$start], $points[$end]);
                if ($d > $maxDist) {
                    $maxDist = $d;
                    $index = $i;
                }
            }
            if ($index !== -1 && $maxDist > $tolerance) {
                $keep[$index] = true;
                $stack[] = [$start, $index];
                $stack[] = [$index, $end];
            }
        }

        $result = [];
        foreach ($points as $i => $p) {
            if ($keep[$i]) {
                $result[] = $p;
            }
        }

        return $result;
    }

    /**
     * 点 p から、線分 a-b までの距離。
     *
     * @param  array{0: float, 1: float}  $p
     * @param  array{0: float, 1: float}  $a
     * @param  array{0: float, 1: float}  $b
     */
    private function distance(array $p, array $a, array $b): float
    {
        $dx = $b[0] - $a[0];
        $dy = $b[1] - $a[1];
        $len2 = $dx * $dx + $dy * $dy;
        if ($len2 === 0.0) {
            return hypot($p[0] - $a[0], $p[1] - $a[1]);
        }
        $t = max(0.0, min(1.0, (($p[0] - $a[0]) * $dx + ($p[1] - $a[1]) * $dy) / $len2));

        return hypot($p[0] - ($a[0] + $t * $dx), $p[1] - ($a[1] + $t * $dy));
    }
}
