<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Calendar\HolidayImporter;
use Illuminate\Console\Command;
use RuntimeException;

final class HolidaysImport extends Command
{
    protected $signature = 'holidays:import';

    protected $description = '内閣府の祝日 CSV(Shift_JIS)を holidays に取り込む(週1回)';

    public function handle(HolidayImporter $importer): int
    {
        try {
            $count = $importer->import();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(__('calendar.imported', ['count' => $count]));

        return self::SUCCESS;
    }
}
