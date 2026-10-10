<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 情報元の URL(地域ページの出典やオープンデータの表示)。
 */
class Source extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fetched_at' => 'datetime'];
    }
}
