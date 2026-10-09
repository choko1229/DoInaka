<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 広告枠(AdSense と、期間付きの PR 枠)。
 */
class AdSlot extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];
    }
}
