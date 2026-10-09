<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MatchType;
use Illuminate\Database\Eloquent\Model;

/**
 * 送信前フィルタの NG ワード。
 */
class NgWord extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['match_type' => MatchType::class];
    }
}
