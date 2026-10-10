<?php

declare(strict_types=1);

use App\Enums\EraTag;
use App\Enums\RegionLevel;
use App\Models\Category;
use App\Models\Region;
use App\Support\ReservedSlugs;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(RegionSeeder::class);
});

it('市区町村が1,741件入り、北方領土の6村と政令市の区は入らない', function (): void {
    expect(Region::query()->where('level', RegionLevel::Prefecture->value)->count())->toBe(47)
        ->and(Region::query()->where('level', RegionLevel::Municipality->value)->count())->toBe(1741);

    // 北方領土の6村
    foreach (['016951', '016969', '016977', '016985', '016993', '017001'] as $code) {
        expect(Region::query()->where('code', $code)->exists())->toBeFalse("北方領土 {$code}");
    }
    // 政令指定都市の区(札幌市中央区)。東京23区(特別区)は入る
    expect(Region::query()->where('name', '札幌市中央区')->exists())->toBeFalse()
        ->and(Region::query()->where('kind', 'ward')->count())->toBe(0)
        ->and(Region::query()->where('kind', 'special_ward')->count())->toBe(23)
        ->and(Region::query()->where('name', '札幌市')->exists())->toBeTrue()
        ->and(Region::query()->where('name', '渋谷区')->exists())->toBeTrue();
});

it('香川県の旧町村が184件(平成34・昭和150)入る', function (): void {
    expect(Region::query()->where('level', RegionLevel::OldMunicipality->value)->count())->toBe(184)
        ->and(Region::query()->where('era', EraTag::Heisei->value)->count())->toBe(34)
        ->and(Region::query()->where('era', EraTag::Showa->value)->count())->toBe(150);

    // 旧町村はすべて、いまの市町を親にする(URL: /kagawa/{市町}/{旧町村}/)
    $kagawa = Region::query()->where('slug', 'kagawa')->firstOrFail();
    $kagawaCities = Region::query()->where('parent_id', $kagawa->id)->pluck('id')->all();
    expect(Region::query()->where('level', RegionLevel::OldMunicipality->value)->whereNotIn('parent_id', $kagawaCities)->count())->toBe(0)
        ->and(count($kagawaCities))->toBe(17);
});

it('昭和の旧村は、のちに入った平成の旧町の下に並び、川津村は坂出市と宇多津町の両方に出る', function (): void {
    // 平成の旧町の下に並ぶ昭和の旧村は101件
    expect(Region::query()->whereNotNull('former_parent_id')->count())->toBe(101);

    $child = Region::query()->whereNotNull('former_parent_id')->firstOrFail();
    $parent = Region::query()->findOrFail($child->former_parent_id);
    expect($parent->era)->toBe(EraTag::Heisei)->and($child->era)->toBe(EraTag::Showa);

    $kawatsu = Region::query()->where('name', '川津村')->firstOrFail();
    $sakaide = Region::query()->where('name', '坂出市')->firstOrFail();
    $utazu = Region::query()->where('name', '宇多津町')->firstOrFail();

    expect($kawatsu->parent_id)->toBe($sakaide->id)
        ->and($kawatsu->alsoParents()->pluck('regions.id')->all())->toBe([$utazu->id])
        ->and($utazu->alsoChildren()->pluck('regions.name')->all())->toContain('川津村', '飯野村')
        ->and($sakaide->children()->pluck('name')->all())->toContain('川津村');
});

it('投稿の受付は全県 ON、巡回は香川県だけ ON', function (): void {
    expect(Region::query()->where('level', RegionLevel::Prefecture->value)->where('accepts_posts', false)->count())->toBe(0)
        ->and(Region::query()->where('level', RegionLevel::Prefecture->value)->where('crawl_enabled', true)->pluck('slug')->all())->toBe(['kagawa']);
});

it('URL のパスが作れる(/kagawa/marugame/ など)', function (): void {
    $marugame = Region::query()->where('name', '丸亀市')->firstOrFail();
    $hanzan = Region::query()->where('parent_id', $marugame->id)->where('level', RegionLevel::OldMunicipality->value)->first();

    expect($marugame->path())->toBe('kagawa/marugame')
        ->and($hanzan?->path())->toStartWith('kagawa/marugame/');
});

it('スラッグは、同じ親の中で一意で、予約語を使わず、英小文字・数字・ハイフンだけ', function (): void {
    $duplicates = DB::table('regions')->select('parent_id', 'slug', DB::raw('COUNT(*) AS n'))->groupBy('parent_id', 'slug')->having('n', '>', 1)->count();
    expect($duplicates)->toBe(0);

    $bad = 0;
    $reserved = 0;
    foreach (Region::query()->pluck('slug') as $slug) {
        if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) !== 1) {
            $bad++;
        }
        if (ReservedSlugs::isReserved($slug)) {
            $reserved++;
        }
    }
    expect($bad)->toBe(0)->and($reserved)->toBe(0);
});

it('読みが重なる市町村には、種別が付く', function (): void {
    // 同じ県の中で、市と町の読みが同じ例が全国にあれば、-shi / -cho が付いている(付いたものと付かないものが混ざらない)
    $suffixed = Region::query()->where('level', RegionLevel::Municipality->value)->where('slug', 'like', '%-shi')->count();
    expect($suffixed)->toBeGreaterThan(0);

    foreach (Region::query()->where('level', RegionLevel::Municipality->value)->where('slug', 'like', '%-shi')->limit(20)->get() as $city) {
        $base = substr($city->slug, 0, -4);
        // 同じ親に、種別違いの同じ読みのものがある
        expect(Region::query()->where('parent_id', $city->parent_id)->where('slug', 'like', $base.'-%')->count())->toBeGreaterThan(1);
    }
});

it('初期データは二重には入らない', function (): void {
    $before = Region::query()->count();

    $this->seed(RegionSeeder::class);

    expect(Region::query()->count())->toBe($before);
});

it('主な地名のスラッグ(香川)', function (): void {
    $kagawa = Region::query()->where('slug', 'kagawa')->firstOrFail();
    $slugs = Region::query()->where('parent_id', $kagawa->id)->pluck('slug')->all();

    expect($slugs)->toContain('takamatsu', 'marugame', 'sakaide', 'zentsuji', 'kanonji', 'sanuki', 'higashikagawa', 'mitoyo', 'shodoshima', 'naoshima', 'utazu', 'kotohira', 'tadotsu', 'manno');
});

it('database/data の CSV は docs/data の元データと同じ', function (): void {
    foreach (['municipalities_jp.csv', 'kagawa_former_municipalities.csv'] as $file) {
        expect(hash_file('sha256', database_path('data/'.$file)))->toBe(hash_file('sha256', base_path('docs/data/'.$file)), $file);
    }
});

it('分類の初期値が入る(イベント用とスポット用)。二重には入らない', function (): void {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    expect(Category::query()->where('target', 'event')->count())->toBe(8)
        ->and(Category::query()->where('target', 'spot')->count())->toBe(8)
        ->and(Category::query()->where('slug', 'festival')->where('target', 'event')->exists())->toBeTrue();
});
