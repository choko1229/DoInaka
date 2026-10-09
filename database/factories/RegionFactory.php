<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RegionLevel;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        return [
            'level' => RegionLevel::Municipality,
            'kind' => 'city',
            'name' => '架空市',
            'name_kana' => 'カクウシ',
            'slug' => 'region-'.fake()->unique()->numberBetween(1, 999999),
            'accepts_posts' => true,
            'crawl_enabled' => false,
        ];
    }

    public function prefecture(): static
    {
        return $this->state(['level' => RegionLevel::Prefecture, 'kind' => 'pref', 'parent_id' => null, 'name' => '架空県']);
    }
}
