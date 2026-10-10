<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** 情報源の候補(情報提供の URL など)。 */
class CrawlCandidate extends Model
{
    protected $guarded = ['id'];
}
