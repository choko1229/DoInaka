<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\FavoriteList;
use App\Enums\SettingKey;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\DB;

/**
 * 人気スコア = 閲覧×重み + お気に入り×重み + 行った!×重み(直近 N 日。設計書11.5)。1時間ごとに集計する。
 */
final class PopularityCalculator
{
    /** @var array<string, string> morph 名 → テーブル */
    private const TABLES = ['event' => 'events', 'spot' => 'spots', 'article' => 'articles'];

    public function __construct(private readonly SettingsService $settings) {}

    /** @return int 更新した件数 */
    public function run(): int
    {
        $weights = $this->weights();
        $days = max(1, $this->settings->int(SettingKey::PopularityWindowDays));
        $since = now()->subDays($days);
        $updated = 0;

        foreach (self::TABLES as $type => $table) {
            $scores = $this->scores($type, $since, $weights);

            // 反応がなくなったものを 0 に戻す
            DB::table($table)->where('popularity_score', '>', 0)->whereNotIn('id', array_keys($scores) ?: [0])->update(['popularity_score' => 0]);

            foreach ($scores as $id => $score) {
                $updated += DB::table($table)->where('id', $id)->update(['popularity_score' => $score]);
            }
        }

        return $updated;
    }

    /**
     * @param  array{view: float, favorite: float, visited: float}  $weights
     * @return array<int, float> id → スコア
     */
    public function scores(string $type, \DateTimeInterface $since, array $weights): array
    {
        $scores = [];
        $add = static function (int $id, float $value) use (&$scores): void {
            $scores[$id] = ($scores[$id] ?? 0.0) + $value;
        };

        foreach (DB::table('page_views')->where('viewable_type', $type)->where('viewed_on', '>=', $since->format('Y-m-d'))
            ->groupBy('viewable_id')->selectRaw('viewable_id, SUM(`count`) AS n')->get() as $row) {
            $add($this->int($row->viewable_id), $this->float($row->n) * $weights['view']);
        }

        foreach (DB::table('favorites')->where('favoritable_type', $type)->where('list', FavoriteList::Favorite->value)->where('created_at', '>=', $since)
            ->groupBy('favoritable_id')->selectRaw('favoritable_id, COUNT(*) AS n')->get() as $row) {
            $add($this->int($row->favoritable_id), $this->float($row->n) * $weights['favorite']);
        }

        foreach (DB::table('visits')->where('visitable_type', $type)->where('visited_on', '>=', $since->format('Y-m-d'))
            ->groupBy('visitable_id')->selectRaw('visitable_id, COUNT(*) AS n')->get() as $row) {
            $add($this->int($row->visitable_id), $this->float($row->n) * $weights['visited']);
        }

        return $scores;
    }

    private function int(mixed $v): int
    {
        return is_numeric($v) ? (int) $v : 0;
    }

    private function float(mixed $v): float
    {
        return is_numeric($v) ? (float) $v : 0.0;
    }

    /** @return array{view: float, favorite: float, visited: float} */
    private function weights(): array
    {
        $w = $this->settings->array(SettingKey::PopularityWeights);
        $num = static fn (mixed $v, float $default): float => is_numeric($v) ? (float) $v : $default;

        return ['view' => $num($w['view'] ?? null, 1), 'favorite' => $num($w['favorite'] ?? null, 5), 'visited' => $num($w['visited'] ?? null, 3)];
    }
}
