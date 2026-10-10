<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 「行った!」(会員は user_id、匿名は Cookie と IP ハッシュで1日1回)。
 */
class Visit extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['visited_on' => 'date'];
    }
}
