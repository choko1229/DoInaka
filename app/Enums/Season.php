<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 季節。アクセント色を決める。
 */
enum Season: string
{
    case Spring = 'spring';
    case Summer = 'summer';
    case Autumn = 'autumn';
    case Winter = 'winter';
}
