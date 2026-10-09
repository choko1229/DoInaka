<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 設置のときに入れる初期データ(地域と分類)。本番でも動く。
 */
class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RegionSeeder::class, CategorySeeder::class]);
    }
}
