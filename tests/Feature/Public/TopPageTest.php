<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Event;
use App\Models\Region;
use App\Models\Spot;
use App\Models\User;

/*
 * トップ(画面デザイン TopPC・Main): FV(見出し・検索・チップ)、今週末のイベント、いまの季節のおすすめ、エリアから探す、人気のスポット、CTA、フッター。
 */
beforeEach(function (): void {
    $this->pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
});

it('FV: 見出し・ひとこと・検索(PC とスマホ)・チップ(今日・今週末・近くで)・ナビ・「投稿する」', function (): void {
    $this->get('/')->assertOk()
        ->assertSee('何もないが、ある。')->assertSee('香川のいなかの、今日と今週末。')
        ->assertSee('獅子舞、棚田、うどん以外…', false)
        ->assertSee('href="/kagawa/events/?when=today"', false)->assertSee('href="/kagawa/events/weekend/"', false)->assertSee('href="/kagawa/events/?near=1"', false)
        ->assertSee('href="/kagawa/events/"', false)->assertSee('href="/kagawa/spots/"', false)->assertSee('href="/kagawa/articles/"', false)->assertSee('href="/kagawa/map/"', false)
        ->assertSee('ログイン')->assertSee('class="sky-cta"', false)->assertSee('hero-header', false);
});

it('ログイン中は、ナビが「ログイン」でなく「マイページ」になる', function (): void {
    $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('マイページ')->assertDontSee('>ログイン<', false);
});

it('スマホのメニュー(JavaScript なしで開く details)に、同じリンクがある', function (): void {
    $html = (string) $this->get('/kagawa/events/')->getContent();

    expect($html)->toContain('sky-header-menu')->toContain('aria-label="メニュー"')->toContain('sky-header-drawer')
        ->and(substr_count($html, 'href="/post/"'))->toBeGreaterThanOrEqual(2);
});

it('今週末のイベント(なければ、これからのイベント)・いまの季節のおすすめ(季節の印と件数)・エリアから探す・CTA', function (): void {
    $city = Region::factory()->create(['parent_id' => $this->pref->id, 'slug' => 'marugame', 'name' => '丸亀市']);
    $event = Event::factory()->published()->onDate(now()->addDays(10)->toDateString())->create(['region_id' => $city->id, 'title' => '秋祭りの獅子舞']);

    $this->get('/')->assertOk()
        ->assertSee('秋祭りの獅子舞')
        ->assertSee('いまの季節のおすすめ')->assertSee('class="season-badge"', false)
        ->assertSee('エリアから探す')->assertSee('丸亀市')->assertSee('イベント 1 件')->assertSee('1市町')
        ->assertSee('地元の「あるある」、教えてください')->assertSee('集落の行事や、知る人ぞ知る景色。名前なしでも投稿できます。');
    expect($event->exists)->toBeTrue();
});

it('人気のスポットと新着の記事が、リストで出る(要件 F-P01: 人気・新着)', function (): void {
    Spot::factory()->create(['region_id' => $this->pref->id, 'title' => '棚田の展望台', 'is_published' => true]);
    Article::factory()->create(['region_id' => $this->pref->id, 'title' => '田舎の一日', 'is_published' => true]);

    $this->get('/')->assertOk()->assertSee('人気のスポット')->assertSee('棚田の展望台')->assertSee('新着の記事・体験談')->assertSee('田舎の一日');
});

it('フッター: トップは「空の色」の切り替えつき、ほかのページは切り替えなしで、リンクとひとこと', function (): void {
    $this->get('/')->assertOk()->assertSee('data-theme-option', false)->assertSee('運営者情報')->assertSee('コンビニまで5km。でも、いいところです。');
    $this->get('/terms/')->assertOk()->assertDontSee('data-theme-option', false)->assertSee('運営者情報')->assertSee('お問い合わせ');
});
