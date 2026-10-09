<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Geo\GeoIpImporter;
use Illuminate\Console\Command;
use Throwable;

final class GeoImport extends Command
{
    protected $signature = 'geo:import';

    protected $description = '日本の IP アドレスの一覧を APNIC から取り込む(週1回)';

    public function handle(GeoIpImporter $importer): int
    {
        try {
            $this->info(__('geo.imported', ['count' => $importer->import()]));
        } catch (Throwable $e) {
            // 取れなければ前の一覧を使い続ける
            $this->warn($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
