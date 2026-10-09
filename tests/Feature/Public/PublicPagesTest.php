<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\Spot;
use Illuminate\Support\Carbon;

/** 県と市、行事、公開済みのイベントを用意する */
function publicWorld(): array
{
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県', 'lat' => 34.34, 'lng' => 134.04]);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'marugame', 'name' => '丸亀市', 'lat' => 34.29, 'lng' => 133.8]);
    $series = EventSeries::factory()->create(['region_id' => $city->id, 'title' => '獅子舞奉納', 'slug' => 'shishimai']);
    $event = Event::factory()->published()->onDate(now()->addDays(10)->toDateString())->create([
        'series_id' => $series->id, 'region_id' => $city->id, 'title' => '獅子舞奉納', 'slug' => 'shishimai', 'lat' => 34.29, 'lng' => 133.8,
    ]);

    return compact('pref', 'city', 'series', 'event');
}

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00', 'Asia/Tokyo'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('トップ・一覧・詳細が開ける', function (): void {
    ['event' => $event] = publicWorld();

    $this->get('/')->assertOk()->assertSee('何もないが、ある。');
    $this->get('/kagawa/events/')->assertOk()->assertSee('獅子舞奉納');
    $this->get("/kagawa/events/{$event->id}-shishimai/")->assertOk()->assertSee('情報元')->assertSee('自治会のお知らせ');
    $this->get('/kagawa/map/')->assertOk();
    $this->get('/kagawa/spots/')->assertOk();
    $this->get('/kagawa/articles/')->assertOk();
});

it('ありえない県のURL、非公開の行事は404', function (): void {
    ['event' => $event, 'city' => $city] = publicWorld();

    $this->get('/nowhere/events/')->assertNotFound();
    $this->get('/nowhere/')->assertNotFound();
    $this->get('/kagawa/nowhere/')->assertNotFound();
    $this->get('/kagawa/events/999999/')->assertNotFound();

    $hidden = Event::factory()->onDate(now()->addDay()->toDateString())->create(['series_id' => $event->series_id, 'region_id' => $city->id, 'is_published' => false]);
    $this->get("/kagawa/events/{$hidden->id}/")->assertNotFound();
});

it('誤登録で消したイベントは410', function (): void {
    ['event' => $event] = publicWorld();
    $event->delete();

    $this->get("/kagawa/events/{$event->id}-shishimai/")->assertStatus(410);
});

it('ローマ字や県が違えば正しいURLへ301で飛ぶ', function (): void {
    ['event' => $event] = publicWorld();
    Region::factory()->prefecture()->create(['slug' => 'ehime', 'name' => '愛媛県']);

    $this->get("/kagawa/events/{$event->id}-old-name/")->assertStatus(301)->assertRedirect("/kagawa/events/{$event->id}-shishimai/");
    $this->get("/kagawa/events/{$event->id}/")->assertStatus(301)->assertRedirect("/kagawa/events/{$event->id}-shishimai/");
    $this->get("/ehime/events/{$event->id}-shishimai/")->assertStatus(301)->assertRedirect("/kagawa/events/{$event->id}-shishimai/");
});

it('スポット・記事・行事マスタの詳細が開ける', function (): void {
    ['city' => $city, 'series' => $series] = publicWorld();
    $spot = Spot::factory()->create(['region_id' => $city->id, 'title' => '棚田の展望台', 'slug' => 'tanada', 'is_published' => true, 'published_at' => now()]);
    $article = Article::factory()->create(['region_id' => $city->id, 'title' => '移住して3年', 'slug' => 'iju', 'is_published' => true, 'published_at' => now()]);

    $this->get("/kagawa/spots/{$spot->id}-tanada/")->assertOk()->assertSee('棚田の展望台');
    $this->get("/kagawa/articles/{$article->id}-iju/")->assertOk()->assertSee('移住して3年');
    $this->get("/kagawa/series/{$series->id}-shishimai/")->assertOk()->assertSee('獅子舞奉納');
});

it('投稿由来の文字をエスケープする(本文・タイトル・改行)', function (): void {
    ['event' => $event] = publicWorld();
    $event->forceFill(['title' => '<script>alert(1)</script>', 'body' => "1行目\n<img src=x onerror=alert(2)>"])->save();

    $html = $this->get("/kagawa/events/{$event->id}-shishimai/")->assertOk()->getContent();
    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->not->toContain('<img src=x onerror')
        ->and($html)->toContain('&lt;img src=x onerror=alert(2)&gt;')
        ->and($html)->toContain('1行目<br />');
});

it('Event の構造化データを出し、JSON から抜け出せない', function (): void {
    ['event' => $event] = publicWorld();
    $event->forceFill(['title' => '</script><b>'])->save();

    $html = $this->get("/kagawa/events/{$event->id}-shishimai/")->getContent();
    expect($html)->toContain('application/ld+json')
        ->and($html)->toContain('"@type":"Event"')
        ->and($html)->not->toContain('</script><b>');
});

it('検索条件の不正な値は無視して落ちない', function (): void {
    publicWorld();

    $this->get('/kagawa/events/?from=2026-13-45&to=abc&lat=999&lng=x&r=-5&sort=evil&page=-3&q='.str_repeat('あ', 500))->assertOk();
    $this->get('/kagawa/events/?lat=34.29&lng=133.8&r=9999&sort=distance')->assertOk();
    $this->get('/kagawa/events/?category[]=1&q[]=x')->assertOk();
});

it('地域ページ: 市は開け、存在しない市町は404、紹介文なしは noindex', function (): void {
    publicWorld();

    $this->get('/kagawa/')->assertOk()->assertSee('香川県')->assertSee('紹介文は準備中です');
    $this->get('/kagawa/marugame/')->assertOk()->assertSee('丸亀市')->assertSee('noindex', false);
    $this->get('/kagawa/marugame/nowhere/')->assertNotFound();
});

it('同じ地域ページへ何度アクセスしても、生成キューには1つだけ', function (): void {
    ['city' => $city] = publicWorld();

    foreach (range(1, 4) as $_) {
        $this->get('/kagawa/marugame/')->assertOk();
    }

    expect(DB::table('region_generation_queue')->where('region_id', $city->id)->count())->toBe(1);
});
it('共有ボタンのURLは do-inaka.net、canonical はメイン', function (): void {
    ['event' => $event] = publicWorld();

    $html = $this->get("/kagawa/events/{$event->id}-shishimai/")->getContent();
    expect($html)->toContain(urlencode('http://do-inaka.net/kagawa/events/'.$event->id.'-shishimai/'))
        ->and($html)->toContain('<link rel="canonical" href="http://localhost/kagawa/events/'.$event->id.'-shishimai/">');
});
