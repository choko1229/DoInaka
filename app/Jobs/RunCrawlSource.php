<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\AiRateLimited;
use App\Jobs\Concerns\DefersWhenAiPaused;
use App\Models\CrawlSource;
use App\Services\Crawl\CrawlRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * 情報源1つの巡回(優先順位4)。制限エラーのときは失敗にせず、リセットのあとに回す。
 */
final class RunCrawlSource implements ShouldQueue
{
    use DefersWhenAiPaused;
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $sourceId) {}

    public function handle(CrawlRunner $runner): void
    {
        $source = CrawlSource::query()->with('region')->find($this->sourceId);
        if ($source === null) {
            return;
        }

        $wait = $this->secondsUntilAiResumes();
        if ($wait !== null) {
            $this->release($wait);

            return;
        }

        try {
            $runner->run($source);
        } catch (AiRateLimited $e) {
            $this->release($this->secondsUntilTime($e->retryAt));
        }
    }
}
