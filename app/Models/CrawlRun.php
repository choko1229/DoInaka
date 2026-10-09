<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 巡回の記録。 */
class CrawlRun extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<CrawlSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(CrawlSource::class, 'crawl_source_id');
    }

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }
}
