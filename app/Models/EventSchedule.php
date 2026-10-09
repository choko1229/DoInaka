<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 日ごとの日程。複数行で、日によって違う時間を表す。is_cancelled は、その日だけの「中止」(消さずに表示だけ変える)。
 *
 * @property int $id
 * @property int $event_id
 * @property Carbon $date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property bool $is_all_day
 * @property string|null $note
 * @property bool $is_cancelled
 */
class EventSchedule extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date' => 'date', 'is_all_day' => 'boolean', 'is_cancelled' => 'boolean'];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
