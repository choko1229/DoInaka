<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * お問い合わせ。メールアドレスは管理者だけが見る。
 */
class Inquiry extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['urgent' => 'boolean', 'ai_check' => 'array', 'handled_at' => 'datetime'];
    }
}
