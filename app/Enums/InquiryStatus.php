<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum InquiryStatus: string
{
    use HasLabel;

    case New = 'new';
    case InProgress = 'in_progress';
    case Done = 'done';
}
