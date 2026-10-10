<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Analytics\PopularityCalculator;
use Illuminate\Console\Command;

final class PopularityCalculate extends Command
{
    protected $signature = 'popularity:calculate';

    protected $description = '人気スコアを集計し直す(1時間ごと)';

    public function handle(PopularityCalculator $calculator): int
    {
        $this->info(__('calendar.popularity_done', ['count' => $calculator->run()]));

        return self::SUCCESS;
    }
}
