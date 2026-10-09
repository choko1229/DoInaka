<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FavoriteList;
use Illuminate\Database\Eloquent\Model;

/**
 * お気に入り・行きたいリスト。
 */
class Favorite extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['list' => FavoriteList::class];
    }
}
