<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Region;
use App\Services\Url\PublicLinks;

/*
 * イベント詳細(画面デザイン EventDetailPC・EventDetail): 上に分類とタグ、情報元の箱、右に日程と「カレンダーに追加」。
 */
beforeEach(function (): void {
    $this->pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $this->city = Region::factory()->create(['parent_id' => $this->pref->id, 'slug' => 'marugame', 'name' => '丸亀市']);
    $this->event = Event::factory()->published()->onDate('2026-10-16')->create(['title' => 'ほたる観察会', 'region_id' => $this->city->id, 'venue_name' => 'ため池']);
});

it('詳細の画面: 見出し・情報元・右の日程とカレンダーに追加・反応ボタン', function (): void {
    $url = app(PublicLinks::class)->event($this->event);

    $this->get($url)->assertOk()
        ->assertSee('ほたる観察会')
        ->assertSee('情報元')
        ->assertSee('日程')
        ->assertSee('カレンダーに追加')
        ->assertSee('行きたい')
        ->assertSee('name="list" value="want_to_go"', false)
        ->assertSee('ログインしてコメントする');
});

it('カレンダーに追加: 開催日ごとの予定を .ics で返す。中止の日は入れない', function (): void {
    EventSchedule::query()->create(['event_id' => $this->event->id, 'date' => '2026-10-17', 'start_time' => '10:00:00', 'end_time' => '14:00:00', 'is_cancelled' => true]);
    EventSchedule::query()->where('event_id', $this->event->id)->where('date', '2026-10-16')->update(['start_time' => '09:00:00', 'end_time' => '15:00:00']);
    $url = rtrim(app(PublicLinks::class)->event($this->event), '/').'/calendar.ics';

    $response = $this->get($url)->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/calendar');
    $body = $response->getContent();
    expect($body)->toContain('BEGIN:VCALENDAR')->toContain('SUMMARY:ほたる観察会')->toContain('DTSTART;TZID=Asia/Tokyo:20261016T090000')->toContain('DTEND;TZID=Asia/Tokyo:20261016T150000')
        ->and($body)->not->toContain('20261017');
});
