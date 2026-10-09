<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** 巡回したページごとのハッシュと取得日時。 */
class CrawlPage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fetched_at' => 'datetime', 'changed_at' => 'datetime'];
    }
}
