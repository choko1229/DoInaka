<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * 更新のきっかけ。
 */
enum UpdateTrigger: string
{
    use HasLabel;

    case Auto = 'auto';
    case Manual = 'manual';
}
