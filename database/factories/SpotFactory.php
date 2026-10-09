<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Region;
use App\Models\Spot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Spot>
 */
class SpotFactory extends Factory
{
    protected $model = Spot::class;

    public function definition(): array
    {
        return [
            'title' => '[集落名]のため池',
            'body' => '夕方に鳥が集まる、静かなため池です。',
            'region_id' => Region::factory(),
            'is_published' => false,
        ];
    }
}
