<?php

declare(strict_types=1);

use App\Models\Region;
use Database\Seeders\InitialDataSeeder;
use Illuminate\Support\Facades\DB;

it('設置の最後に、47都道府県と香川の市町・旧町村が生成キューに1つずつ入る', function (): void {
    $this->seed(InitialDataSeeder::class);
    $this->seed(InitialDataSeeder::class);

    $regions = Region::query()->count();
    expect(Region::query()->whereNull('parent_id')->count())->toBe(47)
        ->and(DB::table('region_generation_queue')->count())->toBe($regions)
        ->and(DB::table('region_generation_queue')->where('reason', 'install')->count())->toBe($regions);
});
