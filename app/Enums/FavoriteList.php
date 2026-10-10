<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * お気に入りのリスト。
 */
enum FavoriteList: string
{
    case Favorite = 'favorite';
    case WantToGo = 'want_to_go';
}
