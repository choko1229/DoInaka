<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventSourceKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * イベントの情報元(Webページ・チラシ・現地確認)。1件もないイベントは公開できない(設計書9.7)。
 *
 * @property int $id
 * @property int $event_id
 * @property EventSourceKind $kind
 * @property string|null $url
 * @property string|null $title
 * @property int|null $media_id
 * @property Carbon|null $checked_at
 * @property bool $is_official
 */
class EventSource extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['kind' => EventSourceKind::class, 'checked_at' => 'date', 'is_official' => 'boolean'];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
