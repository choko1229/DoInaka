<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * 行事のくり返し。
 */
enum Recurrence: string
{
    use HasLabel;

    case Yearly = 'yearly';
    case Irregular = 'irregular';
    case Once = 'once';
}
