<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 公開用の画像(WebP の3サイズ)。
 */
class Media extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['rights_agreed_at' => 'datetime', 'ai_result' => 'array'];
    }
}
