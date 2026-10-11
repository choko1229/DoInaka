<?php

declare(strict_types=1);

namespace App\Services\Region;

use App\Models\Region;

/**
 * 地域ページの「形の略図」(画面デザインの境界の図)。国土数値情報の境界の形データは持たないので、
 * 次の階層(市町・旧町村)の中心の位置を囲む最小の凸の形を、280×150 の図に収めて返す。位置が3つ以上あるときだけ。
 */
final class RegionOutline
{
    private const WIDTH = 280;

    private const HEIGHT = 150;

    private const PAD = 16;

    /**
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

        $xs = array_column($hull, 0);
        $ys = array_column($hull, 1);
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
        foreach ($hull as [$x, $y]) {
            $out[] = [round($offsetX + ($x - $minX) * $ratio * $scale, 1), round($offsetY + ($maxY - $y) * $scale, 1)];
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
