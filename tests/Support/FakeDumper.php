<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\Update\SqlDumper;
use Illuminate\Database\Connection;

final class FakeDumper extends SqlDumper
{
    public int $dumps = 0;

    public int $restores = 0;

    public function dump(Connection $db, string $path, ?array $only = null): int
    {
        $this->dumps++;
        file_put_contents($path, "-- fake dump\n");

        return 1;
    }

    public function restore(Connection $db, string $path): int
    {
        $this->restores++;

        return 1;
    }
}
