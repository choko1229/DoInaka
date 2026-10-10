<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * 旧町村の時代(合併の大きな区切り)。
 */
enum EraTag: string
{
    use HasLabel;

    case Heisei = 'heisei';
    case Showa = 'showa';
}
