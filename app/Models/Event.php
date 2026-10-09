<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use App\Exceptions\EventSourceMissingException;
use Carbon\CarbonInterface;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 開催回(ある行事の、ある年・回の開催)。日ごとの日程は event_schedules。
 *
 * @property int $id
 * @property int $series_id
 * @property string $title
 * @property string|null $slug
 * @property string|null $body
 * @property int $region_id
 * @property int|null $category_id
 * @property string|null $venue_name
 * @property string|null $address
 * @property string|null $lat
 * @property string|null $lng
 * @property string|null $fee
 * @property string|null $url
 * @property EventStatus $status
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property bool $is_postponed
 * @property Carbon|null $postponed_from
 * @property string|null $search_text
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventSchedule> $schedules
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventSource> $sources
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'is_published' => 'boolean',
            'is_postponed' => 'boolean',
            'is_anonymous' => 'boolean',
            'published_at' => 'datetime',
            'postponed_from' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // 情報元が0件のイベントは公開できない(DB のトリガーと二重の守り)
        static::saving(function (Event $event): void {
            if ($event->is_published && ($event->isDirty('is_published') || ! $event->exists) && ! $event->hasSource()) {
                throw new EventSourceMissingException(__('content.event_source_required'));
            }
        });
    }

    /** @return BelongsTo<EventSeries, $this> */
    public function series(): BelongsTo
    {
        return $this->belongsTo(EventSeries::class, 'series_id');
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

    /** @return HasMany<EventSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(EventSchedule::class)->orderBy('date')->orderBy('start_time')->orderBy('id');
    }

    /** @return HasMany<EventSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(EventSource::class)->orderBy('id');
    }

    /** @return MorphToMany<Tag, $this> */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /** @return MorphMany<Revision, $this> */
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable')->latest('id');
    }

    public function hasSource(): bool
    {
        return $this->exists && $this->sources()->exists();
    }

    /**
     * 公開する。情報元がなければ EventSourceMissingException。
     *
     * @throws EventSourceMissingException
     */
    public function publish(?CarbonInterface $at = null): void
    {
        $this->forceFill(['is_published' => true, 'published_at' => $this->published_at ?? ($at ?? now())])->save();
    }

    public function unpublish(): void
    {
        $this->forceFill(['is_published' => false])->save();
    }

    public function firstDate(): ?CarbonInterface
    {
        return $this->schedules->first()?->date;
    }

    public function lastDate(): ?CarbonInterface
    {
        return $this->schedules->last()?->date;
    }

    /** すべての日が中止か(日程がなければ false) */
    public function isFullyCancelled(): bool
    {
        /** @var Collection<int, EventSchedule> $schedules */
        $schedules = $this->schedules;

        return $schedules->isNotEmpty() && $schedules->every(fn (EventSchedule $s): bool => $s->is_cancelled);
    }

    /**
     * 公開側に出す状態: cancelled(中止)/ postponed(延期)/ ended(開催済み)/ undecided(次回未定)/ scheduled(予定)。
     * 中止は消さずに表示だけ変える。日付を変えると「延期」になる(設計書9章・実装指示書フェーズ3)。
     */
    public function displayStatus(): string
    {
        if ($this->status === EventStatus::Cancelled || $this->isFullyCancelled()) {
            return 'cancelled';
        }
        if ($this->status === EventStatus::Undecided) {
            return 'undecided';
        }
        if ($this->status === EventStatus::Ended) {
            return 'ended';
        }

        return $this->is_postponed ? 'postponed' : 'scheduled';
    }
}
