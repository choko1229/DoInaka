<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * 広告枠(AdSense の場所ごとの ON/OFF と、期間付きの PR 枠。設計書14章)。
 *
 * @property int $id
 * @property string $position
 * @property string $kind adsense / pr
 * @property string|null $title
 * @property string|null $body
 * @property string|null $link_url
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 */
class AdSlot extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];
    }
}
