<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Analytics\PageViewCounter;
use Illuminate\Console\Command;

final class PageViewsFlush extends Command
{
    protected $signature = 'pageviews:flush';

    protected $description = '終わった時間の閲覧数を page_view_hours に移し、古い集計を消す(1時間ごと)';

    public function handle(PageViewCounter $counter): int
    {
        $moved = $counter->flush();
        $counter->prune();
        $this->info(__('update.pageviews_flushed', ['hours' => $moved]));

        return self::SUCCESS;
    }
}
