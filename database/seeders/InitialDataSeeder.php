<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Region\RegionPageQueue;
use Illuminate\Database\Seeder;

/**
 * 設置のときに入れる初期データ(地域と分類)。本番でも動く。
 */
class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RegionSeeder::class, CategorySeeder::class]);

        // 設置の最後に、47都道府県と香川の市町・旧町村の紹介文を生成キューへ入れる(設計書9.8。生成そのものはフェーズ6)
        app(RegionPageQueue::class)->enqueueAll('install');
    }
}
