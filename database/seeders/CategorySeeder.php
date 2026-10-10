<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryTarget;
use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * イベントとスポットの分類の初期値。管理画面のマスタから、あとで足したり直したりできる。
 */
class CategorySeeder extends Seeder
{
    /** @var array<string, list<array{0: string, 1: string}>> */
    private const CATEGORIES = [
        'event' => [
            ['祭り・行事', 'festival'], ['市・マルシェ', 'market'], ['体験・ワークショップ', 'experience'],
            ['音楽・舞台', 'performance'], ['スポーツ', 'sports'], ['食', 'food'], ['自然・観察', 'nature'], ['その他', 'other'],
        ],
        'spot' => [
            ['神社・寺', 'shrine-temple'], ['自然・景観', 'scenery'], ['ため池・棚田', 'pond-terrace'], ['食・産直', 'food-direct'],
            ['温泉・銭湯', 'onsen'], ['歴史・町並み', 'history'], ['公園・広場', 'park'], ['その他', 'other'],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $target => $list) {
            foreach ($list as $i => [$name, $slug]) {
                Category::query()->firstOrCreate(
                    ['target' => CategoryTarget::from($target), 'slug' => $slug],
                    ['name' => $name, 'sort_order' => $i + 1],
                );
            }
        }
    }
}
