<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 受け取ったままの元画像(非公開領域に60日保存)。
 */
class MediaOriginal extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }
}
