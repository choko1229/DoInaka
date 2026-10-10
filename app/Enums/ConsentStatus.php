<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ConsentStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Agreed = 'agreed';
    case Objected = 'objected';
}
