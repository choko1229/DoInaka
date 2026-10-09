<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Recurrence;
use Database\Factories\EventSeriesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 行事マスタ(獅子舞・芸術祭などの継続行事)。単発のイベントも1件の series を持つ。
 *
 * @property int $id
 * @property string $title
 * @property string|null $slug
 * @property string|null $summary
 * @property Recurrence $recurrence
 * @property int $region_id
 * @property int|null $category_id
 */
class EventSeries extends Model
{
    /** @use HasFactory<EventSeriesFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'event_series';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['recurrence' => Recurrence::class];
    }

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<Event, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'series_id');
    }
}
