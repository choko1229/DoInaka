<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * イベントの情報元の種類(設計書9.7)。
 */
enum EventSourceKind: string
{
    use HasLabel;

    case Url = 'url';
    case Flyer = 'flyer';
    case Onsite = 'onsite';
}
