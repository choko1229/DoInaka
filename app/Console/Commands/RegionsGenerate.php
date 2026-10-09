<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AiPurpose;
use App\Jobs\GenerateRegionIntro;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiUsage;
use App\Services\Region\RegionPageQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 地域ページの紹介文を、キューの先頭から1つずつ作る(10分ごと。AI が使えて、止まっていないときだけ)。
 * 再生成(priority 1)→ アクセスが多い順。ほかのジョブより後ろ(ai-5)で動くので、空いているときに作られる。
 */
final class RegionsGenerate extends Command
{
    protected $signature = 'regions:generate';

    protected $description = '地域ページの紹介文の生成を、キューの先頭から1つ始める';

    public function handle(RegionPageQueue $queue, AiClient $ai, AiUsage $usage): int
    {
        if (! $ai->isConfigured() || $usage->isPaused()) {
            return self::SUCCESS;
        }

        // 1つずつ(いま動いているものがあれば、次は始めない)
        if (DB::table('region_generation_queue')->where('status', 'running')->where('started_at', '>=', now()->subHour())->exists()) {
            return self::SUCCESS;
        }

        $next = $queue->next();
        if ($next === null) {
            return self::SUCCESS;
        }

        DB::table('region_generation_queue')->where('id', $next['id'])->update(['status' => 'running', 'started_at' => now(), 'updated_at' => now()]);
        GenerateRegionIntro::dispatch($next['region_id'])->onQueue(AiPurpose::RegionIntro->queue());
        $this->info(__('region.started', ['id' => $next['region_id']]));

        return self::SUCCESS;
    }
}
