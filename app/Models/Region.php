<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EraTag;
use App\Enums\RegionLevel;
use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * 地域(都道府県 → 市区町村 → 旧町村)。設計書3.1・9.8。
 *
 * @property int $id
 * @property int|null $parent_id URL 上の親(県は NULL、市区町村は県、旧町村はいまの市区町村)
 * @property int|null $former_parent_id 昭和の旧村が並ぶ、平成の旧町(表示用)
 * @property RegionLevel $level
 * @property string|null $kind
 * @property string|null $code
 * @property string $name
 * @property string|null $name_kana
 * @property string $slug
 * @property string|null $lat
 * @property string|null $lng
 * @property bool $is_active
 * @property bool $accepts_posts
 * @property bool $crawl_enabled
 * @property EraTag|null $era
 * @property Carbon|null $abolished_on
 * @property string|null $merged_into
 */
class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'level' => RegionLevel::class,
            'era' => EraTag::class,
            'is_active' => 'boolean',
            'accepts_posts' => 'boolean',
            'crawl_enabled' => 'boolean',
            'abolished_on' => 'date',
            'merged_at' => 'date',
        ];
    }

    /** @return BelongsTo<Region, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Region, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * 区域が分かれた旧村(このページ)を「この地域の一部」として載せる、ほかの市町。
     *
     * @return BelongsToMany<Region, $this, Pivot, 'pivot'>
     */
    public function alsoParents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'region_also_parents', 'region_id', 'parent_region_id');
    }

    /**
     * この市町のページに「この地域の一部」として載る旧村。
     *
     * @return BelongsToMany<Region, $this, Pivot, 'pivot'>
     */
    public function alsoChildren(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'region_also_parents', 'parent_region_id', 'region_id');
    }

    /** @return HasMany<Event, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return MorphMany<Revision, $this>
     */
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable')->latest('id');
    }

    public function revisionsCount(): int
    {
        return $this->revisions()->count();
    }

    /** `/kagawa/marugame/hanzan/` のような、県から始まるパス(末尾スラッシュなし) */
    public function path(): string
    {
        $segments = [$this->slug];
        $node = $this;
        while ($node->parent_id !== null) {
            $node = $node->parent ?? throw new \LogicException('親がありません。');
            $segments[] = $node->slug;
        }

        return implode('/', array_reverse($segments));
    }
}
