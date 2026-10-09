<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 巡回する情報源(設計書9.6)。
 *
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string $host
 * @property string $kind
 * @property int|null $region_id
 * @property bool $is_active
 * @property bool $is_trusted
 * @property int $clean_approvals
 * @property int $interval_days
 * @property int $unchanged_streak
 * @property int $failure_streak
 * @property int $last_event_count
 * @property Carbon|null $next_run_at
 * @property Carbon|null $last_run_at
 * @property Carbon|null $paused_at
 */
class CrawlSource extends Model
{
    /** 「信頼済み」を提案する、修正なしの承認の連続件数 */
    public const TRUST_SUGGEST_AFTER = 10;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_trusted' => 'boolean',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'paused_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /** @return HasMany<CrawlPage, $this> */
    public function pages(): HasMany
    {
        return $this->hasMany(CrawlPage::class);
    }

    /** @return HasMany<CrawlRun, $this> */
    public function runs(): HasMany
    {
        return $this->hasMany(CrawlRun::class)->latest('id');
    }

    public function isPaused(): bool
    {
        return $this->paused_at !== null;
    }

    /** 修正なしで承認できた件数が10件続いたら、信頼済みにする提案を出す */
    public function trustProposed(): bool
    {
        return ! $this->is_trusted && $this->clean_approvals >= self::TRUST_SUGGEST_AFTER;
    }
}
