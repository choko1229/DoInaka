<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\AiRateLimited;
use App\Jobs\Concerns\DefersWhenAiPaused;
use App\Models\Region;
use App\Services\Region\RegionIntroGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * 地域ページの紹介文を1つ作る(優先順位5。AI を使う順番の最後)。制限エラーのときは、失敗にせず待ちに戻し、
 * リセットのあとに、ほかのジョブが空いているとき(regions:generate)に続ける。
 */
final class GenerateRegionIntro implements ShouldQueue
{
    use DefersWhenAiPaused;
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $regionId) {}

    public function handle(RegionIntroGenerator $generator): void
    {
        $region = Region::query()->find($this->regionId);
        $row = DB::table('region_generation_queue')->where('region_id', $this->regionId)->first();
        if ($region === null || $row === null) {
            return;
        }

        $attempts = is_numeric($row->attempts) ? (int) $row->attempts : 0;
        $priority = is_numeric($row->priority) ? (int) $row->priority : 5;
        $table = DB::table('region_generation_queue')->where('region_id', $this->regionId);
        $table->update(['status' => 'running', 'started_at' => now(), 'attempts' => $attempts + 1, 'updated_at' => now()]);

        try {
            $result = $generator->generate($region);
        } catch (AiRateLimited) {
            // 待ちに戻す(次の regions:generate がリセットのあとに拾う)
            $table->update(['status' => 'pending', 'started_at' => null, 'attempts' => $attempts, 'updated_at' => now()]);

            return;
        }

        $done = in_array($result['status'], ['published', 'unchanged'], true);
        $retry = $result['status'] === 'unavailable' && $attempts + 1 < 3;

        $table->update([
            'status' => $done ? 'done' : ($retry ? 'pending' : 'failed'),
            'priority' => $done ? 5 : $priority,
            'last_error' => $done ? null : mb_substr($result['reason'] ?? $result['status'], 0, 200),
            'processed_at' => $done ? now() : null,
            'started_at' => null,
            'updated_at' => now(),
        ]);
    }
}
