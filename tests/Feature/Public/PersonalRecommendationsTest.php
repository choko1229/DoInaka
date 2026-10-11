<?php

declare(strict_types=1);

use App\Enums\FavoriteList;
use App\Models\Category;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\Region;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Carbon;

/*
 * ログイン会員向けのおすすめ(F-P08): お気に入り・行った!の分類・地域から、これからのイベントを選ぶ(ルールベース)。
 */
beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-14 10:00:00', 'Asia/Tokyo'));
    $this->pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $this->marugame = Region::factory()->create(['parent_id' => $this->pref->id, 'slug' => 'marugame', 'name' => '丸亀市']);
    $this->takamatsu = Region::factory()->create(['parent_id' => $this->pref->id, 'slug' => 'takamatsu', 'name' => '高松市']);
    $this->matsuri = Category::factory()->create(['name' => '祭り・行事', 'slug' => 'matsuri']);
    $this->taiken = Category::factory()->create(['name' => '体験', 'slug' => 'taiken']);
    $this->member = User::factory()->create();
});

afterEach(fn () => Carbon::setTestNow());

function recEvent(string $title, string $date, Region $region, Category $category): Event
{
    return Event::factory()->published()->onDate($date)->create(['title' => $title, 'region_id' => $region->id, 'category_id' => $category->id]);
}

it('お気に入りと同じ分類のこれからのイベントが、トップの「あなたへのおすすめ」に出る。反応済みと過去のものは出ない', function (): void {
    $liked = recEvent('好きな秋祭り', '2026-10-20', $this->marugame, $this->matsuri);
    Favorite::query()->create(['user_id' => $this->member->id, 'favoritable_type' => 'event', 'favoritable_id' => $liked->id, 'list' => FavoriteList::Favorite]);
    recEvent('別の祭りの日', '2026-10-25', $this->takamatsu, $this->matsuri);
    recEvent('終わった祭り', '2026-10-01', $this->takamatsu, $this->matsuri);
    recEvent('関係ない体験', '2026-10-26', $this->takamatsu, $this->taiken);

    $html = (string) $this->actingAs($this->member)->get('/')->assertOk()->assertSee('あなたへのおすすめ')->getContent();
    // 「あなたへのおすすめ」の欄だけを見る(ほかの欄には、全員向けのイベントが並ぶ)
    preg_match('#id="for-you".*?</section>#s', $html, $m);
    expect($m[0])->toContain('別の祭りの日')->not->toContain('好きな秋祭り')->not->toContain('終わった祭り')->not->toContain('関係ない体験');
});

it('同じ地域のイベントも出る(行った!から)。反応がない会員・ログインしていない人には出ない', function (): void {
    $visited = recEvent('行った祭り', '2026-10-15', $this->marugame, $this->matsuri);
    Visit::query()->create(['user_id' => $this->member->id, 'visitable_type' => 'event', 'visitable_id' => $visited->id, 'visited_on' => '2026-10-10']);
    recEvent('丸亀の体験', '2026-10-30', $this->marugame, $this->taiken);

    preg_match('#id="for-you".*?</section>#s', (string) $this->actingAs($this->member)->get('/')->getContent(), $m);
    expect($m[0])->toContain('丸亀の体験');

    $none = User::factory()->create();
    $this->actingAs($none)->get('/')->assertOk()->assertDontSee('あなたへのおすすめ');
    auth()->logout();
    $this->flushSession();
    $this->get('/')->assertOk()->assertDontSee('あなたへのおすすめ');
});
