<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ReplyStatus: string
{
    use HasLabel;

    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
}
