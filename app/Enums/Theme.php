<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 配色の時間帯(テーマ)。背景と文字を決める。
 */
enum Theme: string
{
    case Morning = 'morning';
    case Day = 'day';
    case Evening = 'evening';
    case Night = 'night';
}
