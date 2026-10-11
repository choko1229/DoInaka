<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Region;
use Illuminate\Support\Carbon;

/*
 * イベント一覧(画面デザイン EventsPC・Events): 絞り込み(いつ・エリア・分類の複数・現在地)、
 * 結果の見出しと表示の切り替え(一覧・カレンダー・地図)、番号のページ送り、カレンダー。
 */
beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-14 10:00:00', 'Asia/Tokyo'));
    $this->pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $this->marugame = Region::factory()->create(['parent_id' => $this->pref->id, 'slug' => 'marugame', 'name' => '丸亀市']);
    $this->takamatsu = Region::factory()->create(['parent_id' => $this->pref->id, 'slug' => 'takamatsu', 'name' => '高松市']);
    $this->matsuri = Category::factory()->create(['name' => '祭り・行事', 'slug' => 'matsuri']);
    $this->taiken = Category::factory()->create(['name' => '体験', 'slug' => 'taiken']);
});

afterEach(fn () => Carbon::setTestNow());

function listEvent(string $title, string $date, Region $region, ?Category $category = null): Event
{
    return Event::factory()->published()->onDate($date)->create(['title' => $title, 'region_id' => $region->id, 'category_id' => $category?->id]);
}

it('絞り込みの画面: キーワード・いつ(今日・今週末・今月・期間を指定)・エリア・分類(チェック)・現在地・この条件で探す', function (): void {
    $this->get('/kagawa/events/')->assertOk()
        ->assertSee('香川県のイベント')
        ->assertSee('name="q"', false)
        ->assertSee('name="when" value="today"', false)->assertSee('name="when" value="weekend"', false)->assertSee('name="when" value="month"', false)->assertSee('name="when" value="range"', false)
        ->assertSee('期間を指定')->assertSee('香川県全域')->assertSee('丸亀市')->assertSee('高松市')
        ->assertSee('name="category[]" value="matsuri"', false)->assertSee('現在地から10km以内')->assertSee('この条件で探す');
});

it('「今月」は、今日から月末までのイベントだけ。「エリア」は、その市町のイベントだけ', function (): void {
    listEvent('今月のお祭り', '2026-10-20', $this->marugame);
    listEvent('来月のお祭り', '2026-11-03', $this->marugame);
    listEvent('高松のお祭り', '2026-10-25', $this->takamatsu);

    $this->get('/kagawa/events/?when=month')->assertOk()->assertSee('今月のお祭り')->assertSee('高松のお祭り')->assertDontSee('来月のお祭り');
    $this->get('/kagawa/events/?area=marugame')->assertOk()->assertSee('今月のお祭り')->assertSee('来月のお祭り')->assertDontSee('高松のお祭り');
});

it('分類は、複数(チェックボックス)を選べる。どれかに当てはまればよい', function (): void {
    listEvent('獅子舞', '2026-10-20', $this->marugame, $this->matsuri);
    listEvent('そば打ち', '2026-10-21', $this->marugame, $this->taiken);
    $other = Category::factory()->create(['name' => '食', 'slug' => 'shoku']);
    listEvent('マルシェ', '2026-10-22', $this->marugame, $other);

    $this->get('/kagawa/events/?category[]=matsuri&category[]=taiken')->assertOk()->assertSee('獅子舞')->assertSee('そば打ち')->assertDontSee('マルシェ');
    $this->get('/kagawa/events/?category=matsuri')->assertOk()->assertSee('獅子舞')->assertDontSee('そば打ち');
});

it('結果の見出し: 件数と並び順、表示の切り替え(一覧・カレンダー・地図)', function (): void {
    listEvent('獅子舞', '2026-10-20', $this->marugame);

    $this->get('/kagawa/events/')->assertOk()
        ->assertSee('<b>1件</b>', false)->assertSee('次の日程が近い順')
        ->assertSee('href="/kagawa/events/?view=calendar"', false)->assertSee('href="/kagawa/map/"', false)->assertSee('aria-pressed="true"', false);
});

it('カレンダー表示: 月の格子に、日程のある日のイベントが並ぶ。前の月・次の月へ移れ、絞り込みも保つ', function (): void {
    listEvent('獅子舞', '2026-10-20', $this->marugame);
    listEvent('そば打ち', '2026-11-03', $this->marugame);

    $html = (string) $this->get('/kagawa/events/?view=calendar&month=2026-10')->assertOk()->assertSee('2026年10月')->assertSee('獅子舞')->assertDontSee('そば打ち')
        ->assertSee('href="/kagawa/events/?view=calendar&amp;month=2026-11"', false)->assertSee('href="/kagawa/events/?view=calendar&amp;month=2026-09"', false)->getContent();
    expect($html)->toContain('class="cal"')->toContain('is-today');

    $this->get('/kagawa/events/?view=calendar&month=2026-11&area=marugame')->assertOk()->assertSee('そば打ち')->assertDontSee('獅子舞');
    // 不正な月は、今月
    $this->get('/kagawa/events/?view=calendar&month=abc')->assertOk()->assertSee('2026年10月');
});

it('ページ送り: 番号の丸(いまのページは印つき)。スマホは「もっと見る」', function (): void {
    foreach (range(1, 14) as $i) {
        listEvent("お祭り{$i}", now()->addDays($i)->toDateString(), $this->marugame);
    }

    $this->get('/kagawa/events/')->assertOk()->assertSee('class="pager-num" aria-current="page"', false)->assertSee('href="http://localhost/kagawa/events?page=2"', false)->assertSee('class="btn load-more"', false)->assertSee('rel="next"', false);
});

it('カード: 分類・タグの印(チップ)と、中止は「中止・日付」の赤', function (): void {
    $event = listEvent('中止になった祭り', '2026-10-20', $this->marugame, $this->matsuri);
    $event->forceFill(['status' => EventStatus::Cancelled])->save();

    $this->get('/kagawa/events/')->assertOk()->assertSee('中止・', false)->assertSee('event-card-date is-cancelled', false);
});

it('現在地: チェックすると緯度・経度・半径の欄に入る(JavaScript)。URL に lat・lng・r があれば、近い順の絞り込みになる', function (): void {
    listEvent('近い祭り', '2026-10-20', $this->marugame);

    $this->get('/kagawa/events/')->assertOk()->assertSee('data-near-lat', false)->assertSee('data-near-lng', false)->assertSee('data-near-r', false);
    expect((string) file_get_contents(resource_path('js/public.js')))->toContain('navigator.geolocation')->toContain("data.delete('near')");
});
