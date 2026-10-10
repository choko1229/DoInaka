<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AiPurpose;
use App\Jobs\RunCrawlSource;
use App\Services\Crawl\CrawlRunner;
use Illuminate\Console\Command;

/**
 * 巡回の時刻になった情報源を、キューに入れる(アクセスが少ない時間帯。更新とは1時間ずらす)。
 */
final class CrawlRun extends Command
{
    protected $signature = 'crawl:run';

    protected $description = '巡回の時刻になった情報源をキューに入れる';

    public function handle(CrawlRunner $runner): int
    {
        $count = 0;
        foreach ($runner->due()->get() as $source) {
            RunCrawlSource::dispatch($source->id)->onQueue(AiPurpose::Crawl->queue());
            $count++;
        }

        $this->info(__('crawl.run_done', ['count' => $count]));

        return self::SUCCESS;
    }
}
