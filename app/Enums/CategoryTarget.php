<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * 分類の対象(イベント用とスポット用)。
 */
enum CategoryTarget: string
{
    use HasLabel;

    case Event = 'event';
    case Spot = 'spot';
}
