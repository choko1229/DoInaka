<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * 開催回の状態(設計書3.2)。undecided は「次回未定」の下書き。
 */
enum EventStatus: string
{
    use HasLabel;

    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
    case Ended = 'ended';
    case Undecided = 'undecided';
}
