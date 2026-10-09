<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CheckStatus;

final readonly class CheckResult
{
    public function __construct(
        public string $label,
        public CheckStatus $status,
        public string $detail = '',
    ) {}
}
