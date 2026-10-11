<?php

declare(strict_types=1);

use App\Models\CrawlSource;
use App\Models\Region;

/*
 * 最初の巡回先(香川県の公式な行事の一覧)を、移行で登録する。二重には入らない。地域がなければ何もしない。信頼済みにはしない。
 */
function runInitialSourcesMigration(): void
{
    $migration = require database_path('migrations/2026_10_21_000001_seed_initial_crawl_sources.php');
    $migration->up();
}

it('最初の巡回先が登録され、何度動かしても二重には入らない。信頼済みではない', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'takamatsu', 'name' => '高松市']);

    runInitialSourcesMigration();
    runInitialSourcesMigration();

    expect(CrawlSource::query()->count())->toBe(3);
    $udon = CrawlSource::query()->where('url', 'https://www.my-kagawa.jp/event/')->firstOrFail();
    $matsuri = CrawlSource::query()->where('url', 'like', '%takamatsu%matsuri%')->firstOrFail();
    expect($udon->region_id)->toBe($pref->id)->and($udon->host)->toBe('www.my-kagawa.jp')
        ->and($matsuri->region_id)->toBe($city->id)
        ->and(CrawlSource::query()->where('is_trusted', true)->count())->toBe(0)
        ->and(CrawlSource::query()->where('is_active', true)->count())->toBe(3);
});

it('地域がまだなければ、何も登録しない(エラーにもしない)', function (): void {
    runInitialSourcesMigration();

    expect(CrawlSource::query()->count())->toBe(0);
});
