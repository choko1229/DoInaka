<?php

declare(strict_types=1);

use App\Enums\FavoriteList;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\User;
use App\Services\Analytics\PopularityCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00', 'Asia/Tokyo'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function popularEvent(): Event
{
    $region = Region::factory()->prefecture()->create();
    $series = EventSeries::factory()->create(['region_id' => $region->id]);

    return Event::factory()->create(['series_id' => $series->id, 'region_id' => $region->id]);
}

it('人気スコアは 閲覧×1 + お気に入り×5 + 行った!×3 で計算する', function (): void {
    $event = popularEvent();
    $user = User::factory()->create();
    DB::table('page_views')->insert(['viewable_type' => 'event', 'viewable_id' => $event->id, 'viewed_on' => '2026-10-10', 'count' => 10, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('favorites')->insert(['user_id' => $user->id, 'favoritable_type' => 'event', 'favoritable_id' => $event->id, 'list' => FavoriteList::Favorite->value, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('visits')->insert(['visitable_type' => 'event', 'visitable_id' => $event->id, 'user_id' => $user->id, 'visited_on' => '2026-10-11', 'created_at' => now(), 'updated_at' => now()]);

    app(PopularityCalculator::class)->run();

    expect((float) $event->fresh()->popularity_score)->toBe(10.0 + 5.0 + 3.0);
});

it('31日前の反応は数えず、反応がなくなったものは0に戻る', function (): void {
    $event = popularEvent();
    $event->forceFill(['popularity_score' => 99])->save();
    $user = User::factory()->create();
    DB::table('page_views')->insert(['viewable_type' => 'event', 'viewable_id' => $event->id, 'viewed_on' => now()->subDays(31)->toDateString(), 'count' => 50, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('favorites')->insert(['user_id' => $user->id, 'favoritable_type' => 'event', 'favoritable_id' => $event->id, 'list' => 'favorite', 'created_at' => now()->subDays(31), 'updated_at' => now()]);
    DB::table('visits')->insert(['visitable_type' => 'event', 'visitable_id' => $event->id, 'user_id' => $user->id, 'visited_on' => now()->subDays(31)->toDateString(), 'created_at' => now(), 'updated_at' => now()]);

    app(PopularityCalculator::class)->run();

    expect((float) $event->fresh()->popularity_score)->toBe(0.0);

    // 30日前ちょうどは数える
    DB::table('page_views')->insert(['viewable_type' => 'event', 'viewable_id' => $event->id, 'viewed_on' => now()->subDays(30)->toDateString(), 'count' => 7, 'created_at' => now(), 'updated_at' => now()]);
    app(PopularityCalculator::class)->run();
    expect((float) $event->fresh()->popularity_score)->toBe(7.0);
});

it('お気に入り(行きたいは別のリスト)だけを数える', function (): void {
    $event = popularEvent();
    $user = User::factory()->create();
    DB::table('favorites')->insert(['user_id' => $user->id, 'favoritable_type' => 'event', 'favoritable_id' => $event->id, 'list' => FavoriteList::WantToGo->value, 'created_at' => now(), 'updated_at' => now()]);

    app(PopularityCalculator::class)->run();

    expect((float) $event->fresh()->popularity_score)->toBe(0.0);
});

it('詳細ページの閲覧を数える。管理者・ボットは数えない', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa']);
    $series = EventSeries::factory()->create(['region_id' => $pref->id, 'slug' => 'a']);
    $event = Event::factory()->published()->create(['series_id' => $series->id, 'region_id' => $pref->id, 'slug' => 'a']);
    $url = "/kagawa/events/{$event->id}-a/";

    $this->get($url)->assertOk();
    $this->withHeader('User-Agent', 'Googlebot/2.1')->get($url)->assertOk();
    $admin = User::factory()->admin()->create();
    $this->actingAsVerifiedAdmin($admin)->get($url)->assertOk();

    expect((int) DB::table('page_views')->where('viewable_type', 'event')->where('viewable_id', $event->id)->sum('count'))->toBe(1);
});
