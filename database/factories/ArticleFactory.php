<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Article;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'title' => '棚田の稲刈りを手伝ってきた',
            'body' => '朝から集落の人たちと稲刈りをしました。',
            'region_id' => Region::factory(),
            'is_published' => false,
        ];
    }
}
