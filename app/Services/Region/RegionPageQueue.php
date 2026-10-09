<?php

declare(strict_types=1);

namespace App\Services\Region;

use App\Models\Region;
use Illuminate\Support\Facades\DB;

/**
 * 地域ページの紹介文の生成キュー(設計書9.8)。同じ地域は1件だけ。実際の生成はフェーズ6。
 */
final class RegionPageQueue
{
    /** 紹介文がまだない地域を、キューに1件だけ入れる。入れたら true */
    public function enqueue(Region $region, string $reason = 'access'): bool
    {
        if ($region->intro_body !== null && $region->intro_body !== '') {
            return false;
        }

        return DB::table('region_generation_queue')->insertOrIgnore([
            'region_id' => $region->id,
            'status' => 'pending',
            'reason' => $reason,
            'requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }

    /** 紹介文のない地域すべてを、キューに入れる(設置の最後)。すでにあるものは増やさない */
    public function enqueueAll(string $reason): int
    {
        $added = 0;
        foreach (DB::table('regions')->whereNull('intro_body')->orderBy('id')->pluck('id')->chunk(200) as $ids) {
            $rows = $ids->map(fn (mixed $id): array => [
                'region_id' => $id, 'status' => 'pending', 'reason' => $reason,
                'requested_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ])->all();
            $added += DB::table('region_generation_queue')->insertOrIgnore($rows);
        }

        return $added;
    }

    /** 紹介文が公開(index)してよい状態か: ファクトチェック済みで出典2件以上 */
    public function isIndexable(Region $region): bool
    {
        $sources = $region->intro_sources;

        return $region->intro_body !== null && $region->intro_body !== ''
            && $region->intro_fact_checked && is_array($sources) && count($sources) >= 2;
    }
}
