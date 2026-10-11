<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\Region;
use App\Models\Spot;
use App\Models\User;
use App\Services\Url\PublicLinks;

/*
 * 投稿者プロフィール(/users/{id}/): 表示名・自己紹介と、公開中の投稿だけ。匿名・停止中は出さない(F-A05)。
 */
beforeEach(function (): void {
    $this->pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $this->city = Region::factory()->create(['parent_id' => $this->pref->id, 'slug' => 'marugame', 'name' => '丸亀市']);
    $this->author = User::factory()->create(['name' => 'さぬき太郎', 'bio' => 'うどんが好きです。']);
});

it('プロフィール: 名前・自己紹介・公開中のスポットが出る。メールは出ない', function (): void {
    foreach ([['見晴らし岩', true, false], ['匿名の岩', true, true], ['下書きの岩', false, false]] as [$title, $published, $anonymous]) {
        Spot::factory()->create(['title' => $title, 'region_id' => $this->city->id, 'is_published' => $published])->forceFill(['author_user_id' => $this->author->id, 'is_anonymous' => $anonymous])->save();
    }

    $this->get("/users/{$this->author->id}/")->assertOk()->assertSee('さぬき太郎')->assertSee('うどんが好きです。')->assertSee('見晴らし岩')
        ->assertDontSee('匿名の岩')->assertDontSee('下書きの岩')->assertDontSee($this->author->email)->assertSee('noindex', false);
});

it('停止中の会員と、いない会員は 404', function (): void {
    $this->author->forceFill(['status' => UserStatus::Suspended])->save();
    $this->get("/users/{$this->author->id}/")->assertNotFound();
    $this->get('/users/99999/')->assertNotFound();
});

it('スポットの詳細に、投稿者(会員)へのリンクが出る。匿名には出ない', function (): void {
    $spot = Spot::factory()->create(['title' => '見晴らし岩', 'region_id' => $this->city->id, 'is_published' => true]);
    $spot->forceFill(['author_user_id' => $this->author->id, 'is_anonymous' => false])->save();
    $url = app(PublicLinks::class)->spot($spot);
    $this->get($url)->assertOk()->assertSee("/users/{$this->author->id}/", false)->assertSee('さぬき太郎');

    $spot->forceFill(['is_anonymous' => true])->save();
    $this->get($url)->assertOk()->assertDontSee('さぬき太郎');
});
