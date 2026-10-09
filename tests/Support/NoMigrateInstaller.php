<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\Install\Installer;

/**
 * テストでは本物の migrate を動かさない(RefreshDatabase の接続を切ってしまうため)。
 */
final class NoMigrateInstaller extends Installer
{
    public int $migrations = 0;

    public function migrate(array $db): void
    {
        $this->migrations++;
    }
}
