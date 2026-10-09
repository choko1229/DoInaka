<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\PageViewHour;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * サイト全体の時間別の閲覧数(アクセスの少ない時間帯の計算に使う。設計書10.4)。
 *
 * リクエストごとにキャッシュの数を1つ増やすだけにして、1時間ごとのジョブで page_view_hours に移す。
 * 数えるのは件数だけで、誰が見たかは記録しない。管理者とボットは数えない(呼び出し側で除く)。
 */
final class PageViewCounter
{
    private const PREFIX = 'pageviews:';

    /** 移し忘れを拾うために、さかのぼって見る時間数 */
    private const LOOKBACK_HOURS = 48;

    public function record(?CarbonInterface $at = null): void
    {
        $key = $this->key($this->hourOf($at ?? now()));
        Cache::add($key, 0, now()->addDays(3));
        Cache::increment($key);
    }

    /**
     * 終わった時間の数を page_view_hours に足す。いまの時間はまだ集計中なので触らない。
     *
     * @return int 移した時間数
     */
    public function flush(?CarbonInterface $now = null): int
    {
        $current = $this->hourOf($now ?? now());
        $moved = 0;

        for ($i = 1; $i <= self::LOOKBACK_HOURS; $i++) {
            $hour = $current->subHours($i);
            $count = Cache::pull($this->key($hour));
            if (! is_numeric($count) || (int) $count <= 0) {
                continue;
            }

            $row = PageViewHour::query()->firstOrNew(['hour' => $hour]);
            $row->count = ($row->exists ? $row->count : 0) + (int) $count;
            $row->save();
            $moved++;
        }

        return $moved;
    }

    /** 90日を過ぎた集計を消す(設計書3.6) */
    public function prune(): int
    {
        $deleted = PageViewHour::query()->where('hour', '<', now()->subDays(90))->delete();

        return is_int($deleted) ? $deleted : 0;
    }

    private function hourOf(CarbonInterface $at): CarbonImmutable
    {
        return CarbonImmutable::instance($at)->setTimezone('Asia/Tokyo')->startOfHour();
    }

    private function key(CarbonImmutable $hour): string
    {
        return self::PREFIX.$hour->format('YmdH');
    }
}
