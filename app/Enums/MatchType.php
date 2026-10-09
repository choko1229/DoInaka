<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * NG ワードの一致のしかた。
 */
enum MatchType: string
{
    case Contains = 'contains';
    case Exact = 'exact';
}
