<?php

declare(strict_types=1);

namespace App\Services\Region;

use App\Enums\RegionLevel;
use App\Models\Region;
use Illuminate\Support\Facades\DB;

/**
 * ある地域に含まれる地域の ID(その地域・子・孫)。県の一覧には、市町と旧町村の掲載も含める。
 */
class RegionScope
{
    /**
     * @return list<int>
     */
    public function ids(Region $region): array
    {
        $ids = [$region->id];

        if ($region->level === RegionLevel::OldMunicipality) {
            return $ids;
        }

        $children = DB::table('regions')->where('parent_id', $region->id)->pluck('id')->map(fn (mixed $v): int => is_numeric($v) ? (int) $v : 0)->all();
        $ids = array_merge($ids, $children);

        if ($region->level === RegionLevel::Prefecture && $children !== []) {
            $grand = DB::table('regions')->whereIn('parent_id', $children)->pluck('id')->map(fn (mixed $v): int => is_numeric($v) ? (int) $v : 0)->all();
            $ids = array_merge($ids, $grand);
        }

        // 区域が分かれた旧村は、ほかの市町の範囲にも含める
        if ($region->level === RegionLevel::Municipality) {
            $also = DB::table('region_also_parents')->where('parent_region_id', $region->id)->pluck('region_id')->map(fn (mixed $v): int => is_numeric($v) ? (int) $v : 0)->all();
            $ids = array_merge($ids, $also);
        }

        return array_values(array_unique($ids));
    }
}
