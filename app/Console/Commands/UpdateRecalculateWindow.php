<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Update\UpdateWindowCalculator;
use Illuminate\Console\Command;

final class UpdateRecalculateWindow extends Command
{
    protected $signature = 'update:recalculate-window';

    protected $description = '自動更新と巡回の時間帯(アクセスが最も少ない1時間)を計算し直す(毎週月曜)';

    public function handle(UpdateWindowCalculator $calculator): int
    {
        $hour = $calculator->recalculate();
        $this->info(__('update.window_recalculated', ['hour' => $hour]));

        return self::SUCCESS;
    }
}
