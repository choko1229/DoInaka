<?php

declare(strict_types=1);

namespace App\Services\Region;

use App\Models\Region;

/**
 * 地域ページの「形の図」(画面デザインの境界の図)。280×150 の図に収めて返す。
 *
 * 1. 国土数値情報(行政区域)から作った輪郭のデータ(resources/data/outlines/{都道府県コード}.json。
 *    php artisan regions:build-outlines で作る軽いデータ)があれば、その実際の形を使う。
 * 2. なければ、次の階層(市町・旧町村)の中心の位置を囲む最小の凸の形(略図)。位置が3つ以上あるときだけ。
 */
final class RegionOutline
{
    private const WIDTH = 280;

    private const HEIGHT = 150;

    private const PAD = 16;

    /**
     * @return array{rings: list<list<array{0: float, 1: float}>>, inner: list<list<array{0: float, 1: float}>>, real: bool}|null 図の中の点の並び(リングごと)・合併前の町の輪郭(点線)・実際の形か(false は略図)
     */
    public function shape(Region $region): ?array
    {
        $real = $this->realRings($region);
        if ($real !== null) {
            // 図の大きさは、主な部分(最大の面積の 30% 以上のリング)で決める。離れた小さな島で、本土が小さくなりすぎないように。
            // 図の外に出る小さなリングは省く
            $main = $this->mainRings($real);
            $inner = $this->formerRings($region);

            return ['rings' => $this->inFrame($this->fit($real, $main)), 'inner' => $this->inFrame($this->fit($inner, $main)), 'real' => true];
        }

        $points = $this->points($region);

        return $points === null ? null : ['rings' => [$points], 'inner' => [], 'real' => false];
    }

    /**
     * 主な部分のリング(最大の面積の 30% 以上)。
     *
     * @param  list<list<array{0: float, 1: float}>>  $rings
     * @return list<list<array{0: float, 1: float}>>
     */
    private function mainRings(array $rings): array
    {
        $areas = array_map(function (array $ring): float {
            $sum = 0.0;
            for ($i = 0, $n = count($ring); $i < $n - 1; $i++) {
                $sum += $ring[$i][0] * $ring[$i + 1][1] - $ring[$i + 1][0] * $ring[$i][1];
            }

            return abs($sum / 2);
        }, $rings);
        $largest = $areas === [] ? 0.0 : max($areas);

        $main = [];
        foreach ($rings as $i => $ring) {
            if ($areas[$i] >= $largest * 0.3) {
                $main[] = $ring;
            }
        }

        return $main === [] ? $rings : $main;
    }

    /**
     * 図の枠(280×150)に、すっかり収まらないリングは省く。
     *
     * @param  list<list<array{0: float, 1: float}>>  $rings
     * @return list<list<array{0: float, 1: float}>>
     */
    private function inFrame(array $rings): array
    {
        return array_values(array_filter($rings, function (array $ring): bool {
            foreach ($ring as [$x, $y]) {
                if ($x < 0 || $x > self::WIDTH || $y < 0 || $y > self::HEIGHT) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * 合併前の町の輪郭(点線で描く)。合併前の町が2つ以上、データにあるときだけ。
     *
     * @return list<list<array{0: float, 1: float}>>
     */
    private function formerRings(Region $region): array
    {
        $code = (string) $region->code;
        $file = resource_path('data/outlines/'.substr($code, 0, 2).'.json');
        if (strlen($code) !== 6 || ! is_file($file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($file), true);
        $former = is_array($data) && is_array($data['former'] ?? null) ? $data['former'] : [];

        $rings = [];
        $found = 0;
        foreach ($region->children()->whereNotNull('abolished_on')->pluck('name') as $name) {
            $towns = is_string($name) && is_array($former[$name] ?? null) ? $former[$name] : [];
            if ($towns === []) {
                continue;
            }
            $found++;
            foreach ($towns as $ring) {
                $points = [];
                foreach (is_array($ring) ? $ring : [] as $p) {
                    if (is_array($p) && is_numeric($p[0] ?? null) && is_numeric($p[1] ?? null)) {
                        $points[] = [(float) $p[0], (float) $p[1]];
                    }
                }
                if (count($points) >= 4) {
                    $rings[] = $points;
                }
            }
        }

        return $found >= 2 ? $rings : [];
    }

    /**
     * 略図: 次の階層の位置を囲む凸の形。
     *
     * @return list<array{0: float, 1: float}>|null 図の中の点の並び(時計回り)
     */
    public function points(Region $region): ?array
    {
        $coords = [];
        foreach ($region->children()->where('is_active', true)->whereNotNull('lat')->whereNotNull('lng')->get(['lat', 'lng']) as $child) {
            $coords[] = [(float) $child->lng, (float) $child->lat];
        }
        if (count($coords) < 3) {
            return null;
        }

        $hull = $this->hull($coords);
        if (count($hull) < 3) {
            return null;
        }

        return $this->fit([$hull])[0];
    }

    /**
     * 国土数値情報から作った、実際の輪郭(経度・緯度のリング)。市区町村コード(6桁の先頭5桁)で探す。都道府県は「{県コード}000」。
     *
     * @return list<list<array{0: float, 1: float}>>|null
     */
    private function realRings(Region $region): ?array
    {
        $code = (string) $region->code;
        if (strlen($code) !== 6) {
            return null;
        }
        $pref = substr($code, 0, 2);
        $file = resource_path('data/outlines/'.$pref.'.json');
        if (! is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);
        $rings = is_array($data) ? ($data[substr($code, 0, 5)] ?? null) : null;
        if (! is_array($rings) || $rings === []) {
            return null;
        }

        $result = [];
        foreach ($rings as $ring) {
            $points = [];
            foreach (is_array($ring) ? $ring : [] as $p) {
                if (is_array($p) && is_numeric($p[0] ?? null) && is_numeric($p[1] ?? null)) {
                    $points[] = [(float) $p[0], (float) $p[1]];
                }
            }
            if (count($points) >= 4) {
                $result[] = $points;
            }
        }

        return $result === [] ? null : $result;
    }

    /**
     * 経度・緯度のリングを、280×150 の図に収める(縦横の比をそろえる)。$basis があれば、その範囲で収める(外周の図に、内側の線を重ねるため)。
     *
     * @param  list<list<array{0: float, 1: float}>>  $rings
     * @param  list<list<array{0: float, 1: float}>>|null  $basis
     * @return list<list<array{0: float, 1: float}>>
     */
    private function fit(array $rings, ?array $basis = null): array
    {
        $xs = [];
        $ys = [];
        foreach ($basis ?? $rings as $ring) {
            foreach ($ring as [$x, $y]) {
                $xs[] = $x;
                $ys[] = $y;
            }
        }
        if ($xs === [] || $ys === []) {
            return $rings;
        }
        $minX = min($xs);
        $maxX = max($xs);
        $minY = min($ys);
        $maxY = max($ys);
        $spanX = max($maxX - $minX, 1.0E-6);
        $spanY = max($maxY - $minY, 1.0E-6);
        // 緯度1度は経度1度より長いので、縦横の比をそろえてから収める
        $ratio = cos(deg2rad(($minY + $maxY) / 2));
        $w = $spanX * $ratio;
        $scale = min((self::WIDTH - 2 * self::PAD) / $w, (self::HEIGHT - 2 * self::PAD) / $spanY);
        $offsetX = (self::WIDTH - $w * $scale) / 2;
        $offsetY = (self::HEIGHT - $spanY * $scale) / 2;

        $out = [];
        foreach ($rings as $ring) {
            $fitted = [];
            foreach ($ring as [$x, $y]) {
                $fitted[] = [round($offsetX + ($x - $minX) * $ratio * $scale, 1), round($offsetY + ($maxY - $y) * $scale, 1)];
            }
            $out[] = $fitted;
        }

        return $out;
    }

    /**
     * 凸包(Andrew の単調な鎖)。
     *
     * @param  list<array{0: float, 1: float}>  $points
     * @return list<array{0: float, 1: float}>
     */
    private function hull(array $points): array
    {
        usort($points, fn (array $a, array $b): int => $a[0] <=> $b[0] ?: $a[1] <=> $b[1]);

        $lower = [];
        foreach ($points as $p) {
            while (count($lower) >= 2 && $this->cross($lower[count($lower) - 2], $lower[count($lower) - 1], $p) <= 0) {
                array_pop($lower);
            }
            $lower[] = $p;
        }
        $upper = [];
        foreach (array_reverse($points) as $p) {
            while (count($upper) >= 2 && $this->cross($upper[count($upper) - 2], $upper[count($upper) - 1], $p) <= 0) {
                array_pop($upper);
            }
            $upper[] = $p;
        }
        array_pop($lower);
        array_pop($upper);

        return [...$lower, ...$upper];
    }

    /**
     * 3点の向き(外積)。正なら反時計回り。
     *
     * @param  array{0: float, 1: float}  $o
     * @param  array{0: float, 1: float}  $a
     * @param  array{0: float, 1: float}  $b
     */
    private function cross(array $o, array $a, array $b): float
    {
        return ($a[0] - $o[0]) * ($b[1] - $o[1]) - ($a[1] - $o[1]) * ($b[0] - $o[0]);
    }
}
