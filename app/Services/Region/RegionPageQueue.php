<?php

declare(strict_types=1);

namespace App\Services\Region;

use App\Models\Region;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * 地域ページの紹介文の生成キュー(設計書9.8)。同じ地域は1件だけ。アクセスが多い順に作り、
 * 管理画面・管理者バーの「再生成」は先頭(priority 1)に入る。
 */
final class RegionPageQueue
{
    /** 紹介文がまだない地域を、キューに1件だけ入れる。入れたら true。すでにあれば、アクセスの数だけ増やす */
    public function enqueue(Region $region, string $reason = 'access'): bool
    {
        if ($region->intro_body !== null && $region->intro_body !== '') {
            return false;
        }

        $inserted = DB::table('region_generation_queue')->insertOrIgnore([
            'region_id' => $region->id,
            'status' => 'pending',
            'reason' => $reason,
            'hits' => 1,
            'requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;

        if (! $inserted) {
            DB::table('region_generation_queue')->where('region_id', $region->id)->increment('hits');
        }

        return $inserted;
    }

    /**
     * 再生成: キューの先頭に入れる。新しい紹介文が確認を通るまで、いまの紹介文を出し続ける。
     * 「すぐ作る」(準備中のページ)も同じ。
     */
    public function regenerate(Region $region, string $reason = 'admin'): void
    {
        DB::table('region_generation_queue')->updateOrInsert(
            ['region_id' => $region->id],
            ['status' => 'pending', 'reason' => $reason, 'priority' => 1, 'last_error' => null, 'started_at' => null, 'requested_at' => now(), 'updated_at' => now(), 'created_at' => now()],
        );
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

    /**
     * 次に作る地域のキューの行: 再生成(priority 1)→ アクセスが多い順 → 古い順。止まったまま(1時間)の行も拾う。
     *
     * @return array{id: int, region_id: int, priority: int, attempts: int}|null
     */
    public function next(): ?array
    {
        $row = DB::table('region_generation_queue')
            ->where(fn (Builder $q) => $q->where('status', 'pending')->orWhere(fn (Builder $r) => $r->where('status', 'running')->where('started_at', '<', now()->subHour())))
            ->orderBy('priority')->orderByDesc('hits')->orderBy('id')
            ->first();
        if ($row === null) {
            return null;
        }

        $int = static fn (mixed $v): int => is_numeric($v) ? (int) $v : 0;

        return ['id' => $int($row->id), 'region_id' => $int($row->region_id), 'priority' => $int($row->priority), 'attempts' => $int($row->attempts)];
    }

    /** 紹介文が公開(index)してよい状態か: ファクトチェック済みで出典2件以上 */
    public function isIndexable(Region $region): bool
    {
        $sources = $region->intro_sources;

        return $region->intro_body !== null && $region->intro_body !== ''
            && $region->intro_fact_checked && is_array($sources) && count($sources) >= 2;
    }
}
