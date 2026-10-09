<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Region\RegionImporter;
use Illuminate\Database\Seeder;

/**
 * 全47都道府県・全市区町村(1,741)・香川県の旧町村(184)を入れる(実装指示書 フェーズ3)。
 */
class RegionSeeder extends Seeder
{
    public function run(RegionImporter $importer): void
    {
        $importer->import(
            database_path('data/municipalities_jp.csv'),
            database_path('data/kagawa_former_municipalities.csv'),
        );
    }
}
