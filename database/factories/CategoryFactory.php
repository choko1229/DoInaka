<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryTarget;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'target' => CategoryTarget::Event,
            'name' => '祭り・行事',
            'slug' => 'cat-'.fake()->unique()->numberBetween(1, 999999),
            'sort_order' => 1,
            'is_active' => true,
        ];
    }

    public function spot(): static
    {
        return $this->state(['target' => CategoryTarget::Spot, 'name' => '神社・寺']);
    }
}
