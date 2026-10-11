<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use Illuminate\Support\Carbon;

function seoWorld(int $events): array
{
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $series = EventSeries::factory()->create(['region_id' => $pref->id, 'title' => '行事']);
    for ($i = 0; $i < $events; $i++) {
        Event::factory()->published()->onDate(now()->addDays(5 + $i)->toDateString())->create(['series_id' => $series->id, 'region_id' => $pref->id, 'title' => "行事{$i}"]);
    }

    return compact('pref', 'series');
}

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00', 'Asia/Tokyo'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('掲載4件の一覧は noindex でサイトマップになく、5件になると index になり入る', function (): void {
    seoWorld(4);
    $this->get('/kagawa/events/')->assertOk()->assertSee('noindex, follow', false);
    expect($this->get('/sitemap-lists.xml')->getContent())->not->toContain('/kagawa/events/');

    $series = EventSeries::query()->firstOrFail();
    Event::factory()->published()->onDate(now()->addDays(30)->toDateString())->create(['series_id' => $series->id, 'region_id' => $series->region_id, 'title' => '5件目']);

    $this->get('/kagawa/events/')->assertOk()->assertDontSee('noindex', false);
    expect($this->get('/sitemap-lists.xml')->getContent())->toContain('/kagawa/events/');
});

it('?when= 付きは noindex で、canonical は条件なしのURL', function (): void {
    seoWorld(6);

    $html = $this->get('/kagawa/events/?when=today')->assertOk()->assertSee('noindex, follow', false)->getContent();
    expect($html)->toContain('<link rel="canonical" href="http://localhost/kagawa/events/">');
    $this->get('/kagawa/events/?from=2026-10-20')->assertSee('noindex', false);
});

it('固定の絞り込みページ(今週末)は、件数が足りれば index', function (): void {
    seoWorld(6);

    $this->get('/kagawa/events/weekend/')->assertOk()->assertSee('今週末');
    $this->get('/kagawa/events/category/nothing/')->assertNotFound();
});

it('サイトマップは種類別で、メインのホストで出す。個別ページを含む', function (): void {
    seoWorld(1);

    $index = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($index)->toContain('<sitemapindex')->and($index)->toContain('http://localhost/sitemap-events.xml');
    $events = $this->get('/sitemap-events.xml')->assertOk()->getContent();
    expect($events)->toContain('<loc>http://localhost/kagawa/events/')->and($events)->not->toContain('do-inaka.net');
    $this->get('/sitemap-unknown.xml')->assertNotFound();
});

it('紹介文なしの地域ページは noindex でサイトマップに入らない。条件を満たすと入る', function (): void {
    ['pref' => $pref] = seoWorld(0);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'marugame', 'name' => '丸亀市']);

    $this->get('/kagawa/marugame/')->assertSee('noindex', false);
    expect($this->get('/sitemap-regions.xml')->getContent())->not->toContain('marugame');

    // 出典が1件だけ・ファクトチェック前は、まだ index にしない
    $city->forceFill(['intro_body' => '丸亀市の紹介文です。', 'intro_sources' => [['url' => 'https://example.com/a']], 'intro_fact_checked' => true])->save();
    $this->get('/kagawa/marugame/')->assertSee('noindex', false);

    $city->forceFill(['intro_sources' => [['url' => 'https://example.com/a'], ['url' => 'https://example.com/b']]])->save();
    $this->get('/kagawa/marugame/')->assertDontSee('noindex', false);
    expect($this->get('/sitemap-regions.xml')->getContent())->toContain('/kagawa/marugame/');
});

it('robots.txt は管理画面を除き、サイトマップを案内する', function (): void {
    $body = $this->get('/robots.txt')->assertOk()->getContent();

    expect($body)->toContain('Disallow: /admin/')->and($body)->toContain('Sitemap: http://localhost/sitemap.xml');
});

it('public/robots.txt(静的ファイル)を置かない: Web サーバーが先に返してしまい、robots.txt のルート(公開前モードの全体禁止・サイトマップの案内)が効かなくなる', function (): void {
    expect(file_exists(public_path('robots.txt')))->toBeFalse();
    expect(file_exists(public_path('sitemap.xml')))->toBeFalse();
});
