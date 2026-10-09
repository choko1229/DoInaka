<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\HeldContentScope;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 記事・体験談。
 *
 * @property int $id
 * @property string $title
 * @property string|null $slug
 * @property string|null $body
 * @property int $region_id
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property string|null $search_text
 */
#[ScopedBy([HeldContentScope::class])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'is_anonymous' => 'boolean', 'published_at' => 'datetime'];
    }

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * 公開された写真(処理済みの画像)
     *
     * @return MorphMany<Media, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->whereNotNull('path_large')->orderBy('sort_order')->orderBy('id');
    }

    /** @return MorphToMany<Tag, $this> */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /** @return HasMany<ArticleRelation, $this> */
    public function relations(): HasMany
    {
        return $this->hasMany(ArticleRelation::class);
    }

    /** @return MorphMany<Revision, $this> */
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable')->latest('id');
    }
}
