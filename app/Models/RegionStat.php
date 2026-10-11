<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 地域の人口・面積(統計からの取り込み。時点と出典つき)。
 *
 * @property int $id
 * @property int $region_id
 * @property int|null $population
 * @property int|null $population_year
 * @property string|null $area_km2
 * @property string|null $source_label
 * @property string|null $source_url
 */
class RegionStat extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
