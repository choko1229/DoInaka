<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\Update\DirectorySwapper;
use RuntimeException;

final class FailingRollbackSwapper extends DirectorySwapper
{
    public function rollback(string $current, string $old): void
    {
        throw new RuntimeException('戻せません');
    }
}
