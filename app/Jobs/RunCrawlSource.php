<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AiPurpose;
use App\Exceptions\AiRateLimited;
use App\Jobs\Concerns\DefersWhenAiPaused;
use App\Models\CrawlSource;
use App\Services\Crawl\CrawlRunner;
use App\Services\Cron\WebCronBudget;
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

    /** 大きなモデルは1ページの解析に数十秒かかる。キューの既定(60秒)で打ち切られないようにする */
    public int $timeout = 600;

    /** アクセスで動かすときの、1回の実行時間(延ばせたとき) */
    public const WEB_SECONDS = 150;

    public function __construct(public readonly int $sourceId) {}

    public function handle(CrawlRunner $runner, WebCronBudget $budget): void
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

        // アクセスで動かしているときは、実行時間を延ばせれば延ばす(同じサイトへ10秒あけて読み、1ページの解析に数十秒かかるため、
        // 25秒では1ページも進めない)。延ばせないときは、従来どおり短い上限で区切る
        if (config()->boolean('app.crawl_web_extend', true)) {
            $budget->extend(self::WEB_SECONDS);
        }

        try {
            $run = $runner->run($source, $budget->deadline());
            // アクセスで動かしていて、時間の上限で区切ったときは、続きを次の実行に回す(失敗にはしない)
            if ($run->status === 'deferred' && $run->error === 'time_budget') {
                self::dispatch($this->sourceId)->onQueue(AiPurpose::Crawl->queue())->delay(now()->addSeconds(10));
            }
        } catch (AiRateLimited $e) {
            $this->release($this->secondsUntilTime($e->retryAt));
        }
    }
}
