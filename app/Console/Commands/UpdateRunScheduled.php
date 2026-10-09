<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Update\AutoUpdater;
use Illuminate\Console\Command;
use Throwable;

final class UpdateRunScheduled extends Command
{
    protected $signature = 'update:run-scheduled';

    protected $description = '更新を確認し、自動更新が ON なら適用する(定期処理)';

    public function handle(AutoUpdater $updater): int
    {
        try {
            $run = $updater->runScheduled();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info($run === null ? __('update.nothing_to_apply') : __('update.applied', ['status' => $run->status->value]));

        return self::SUCCESS;
    }
}
