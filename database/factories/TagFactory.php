<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return ['name' => 'タグ'.fake()->unique()->numberBetween(1, 99999), 'slug' => 'tag-'.fake()->unique()->numberBetween(1, 99999)];
    }
}
