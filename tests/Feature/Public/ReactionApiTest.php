<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Favorite;
use App\Models\Region;
use App\Models\User;
use App\Models\Visit;

function reactionEvent(): Event
{
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa']);
    $series = EventSeries::factory()->create(['region_id' => $pref->id]);

    return Event::factory()->published()->onDate(now()->addDay()->toDateString())->create(['series_id' => $series->id, 'region_id' => $pref->id]);
}

it('未ログインはログイン画面へ(JSONなら401とログインURL)', function (): void {
    $event = reactionEvent();

    $this->post("/api/v1/visits/event/{$event->id}")->assertRedirect('/login/');
    $this->postJson("/api/v1/favorites/event/{$event->id}")->assertStatus(401)->assertJson(['login_required' => true]);
    expect(Favorite::query()->count())->toBe(0);
});

it('お気に入りは押すたびに入り・切れ、行った!は1人1回', function (): void {
    $event = reactionEvent();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson("/api/v1/favorites/event/{$event->id}")->assertOk()->assertJson(['on' => true, 'count' => 1]);
    $this->actingAs($user)->postJson("/api/v1/favorites/event/{$event->id}")->assertOk()->assertJson(['on' => false, 'count' => 0]);

    $this->actingAs($user)->postJson("/api/v1/visits/event/{$event->id}")->assertOk()->assertJson(['on' => true, 'count' => 1]);
    $this->actingAs($user)->postJson("/api/v1/visits/event/{$event->id}")->assertOk()->assertJson(['count' => 1]);
    expect(Visit::query()->count())->toBe(1);
});

it('存在しない種類・非公開・数字でないIDは404', function (): void {
    $event = reactionEvent();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson("/api/v1/favorites/user/{$event->id}")->assertNotFound();
    $this->actingAs($user)->postJson('/api/v1/favorites/event/999999')->assertNotFound();
    $this->actingAs($user)->postJson('/api/v1/favorites/event/abc')->assertNotFound();
    $event->unpublish();
    $this->actingAs($user)->postJson("/api/v1/favorites/event/{$event->id}")->assertNotFound();
});

it('絞り込みの部分更新はHTMLを返し、不正な県は404', function (): void {
    reactionEvent();

    $this->getJson('/api/v1/events?pref=kagawa&q=')->assertOk()->assertJsonStructure(['html', 'total']);
    $this->getJson('/api/v1/events?pref=nowhere')->assertNotFound();
    $this->getJson('/api/v1/events')->assertNotFound();
});

it('未ログインの戻り先は、Referer が外部でも同じサイトのパスだけ', function (): void {
    $event = reactionEvent();

    $this->withHeader('referer', 'https://evil.example/steal?x=1')->post("/api/v1/visits/event/{$event->id}")->assertRedirect('/login/');

    expect(session('url.intended'))->toBe('/steal');
});
