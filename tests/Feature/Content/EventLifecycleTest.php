<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\Recurrence;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\EventSeries;
use App\Models\Revision;
use App\Models\Tag;
use App\Models\User;
use App\Services\Content\EventLifecycle;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->lifecycle = app(EventLifecycle::class);
    $this->actor = User::factory()->admin()->create();
    Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00', 'Asia/Tokyo'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function makePublished(array $dates, array $seriesOverride = []): Event
{
    $series = EventSeries::factory()->create($seriesOverride);
    $event = Event::factory()->create(['series_id' => $series->id]);
    foreach ($dates as $date) {
        $event->schedules()->create(['date' => $date, 'start_time' => '09:00', 'end_time' => '15:00']);
    }
    $event->sources()->create(['kind' => 'url', 'url' => 'https://example.com', 'title' => '情報元']);
    $event->publish();

    return $event->refresh();
}

it('中止にした日は消えずに残り、公開側で「中止」と表示される', function (): void {
    $event = makePublished(['2026-10-18', '2026-10-19']);
    $schedule = $event->schedules()->firstOrFail();

    $this->lifecycle->cancelDay($schedule, $this->actor, '雨天のため');

    expect(EventSchedule::query()->where('event_id', $event->id)->count())->toBe(2)
        ->and($schedule->refresh()->is_cancelled)->toBeTrue()
        ->and($event->refresh()->status)->toBe(EventStatus::Scheduled)
        ->and($event->displayStatus())->toBe('scheduled');

    $html = view('components.event-card', ['href' => '/x', 'title' => $event->title, 'status' => $event->displayStatus()])->render();
    expect($html)->not->toContain('中止');

    // 全部の日を中止にすると、開催回も「中止」になる(消えない)
    $this->lifecycle->cancelDay($event->schedules()->reorder('date', 'desc')->firstOrFail(), $this->actor);
    $event->refresh();
    expect($event->status)->toBe(EventStatus::Cancelled)
        ->and($event->displayStatus())->toBe('cancelled')
        ->and(Event::query()->whereKey($event->id)->exists())->toBeTrue()
        ->and($event->is_published)->toBeTrue();

    $html = view('components.event-card', ['href' => '/x', 'title' => $event->title, 'status' => $event->displayStatus()])->render();
    expect($html)->toContain('中止')->toContain('cancelled');

    // 変更の履歴に残る(理由つき)
    expect(Revision::query()->where('revisionable_type', 'event')->where('revisionable_id', $event->id)->where('reason', '雨天のため')->exists())->toBeTrue();
});

it('中止を取り消すと「予定」に戻る', function (): void {
    $event = makePublished(['2026-10-18']);
    $schedule = $event->schedules()->firstOrFail();
    $this->lifecycle->cancelDay($schedule, $this->actor);
    expect($event->refresh()->status)->toBe(EventStatus::Cancelled);

    $this->lifecycle->restoreDay($schedule->refresh(), $this->actor);

    expect($event->refresh()->status)->toBe(EventStatus::Scheduled)->and($schedule->refresh()->is_cancelled)->toBeFalse();
});

it('開催回ごと中止にすると、すべての日が中止になり、消えない', function (): void {
    $event = makePublished(['2026-10-18', '2026-10-19']);

    $this->lifecycle->cancelEvent($event, $this->actor, '台風');

    $event->refresh();
    expect($event->status)->toBe(EventStatus::Cancelled)
        ->and($event->schedules->every(fn (EventSchedule $s): bool => $s->is_cancelled))->toBeTrue()
        ->and($event->schedules)->toHaveCount(2);
});

it('延期は、公開側で「延期」と表示される', function (): void {
    $html = view('components.event-card', ['href' => '/x', 'title' => 't', 'status' => 'postponed'])->render();

    expect($html)->toContain('延期');
});

it('来年分のコピーで日付が1年進む(うるう年の2月29日を含む)', function (): void {
    $event = makePublished(['2027-10-11', '2028-02-29', '2027-02-28', '2024-02-29']);

    $copy = $this->lifecycle->copyForNextYear($event, $this->actor);

    expect($copy->schedules->map(fn (EventSchedule $s): string => $s->date->toDateString())->all())->toBe([
        '2025-02-28', // 2024-02-29 + 1年 → 来年に29日がないので28日
        '2028-02-28', // 2027-02-28 + 1年
        '2028-10-11', // 2027-10-11 + 1年
        '2029-02-28', // 2028-02-29 + 1年 → 2029年に29日がないので28日
    ]);
});

it('来年分のコピーは公開前の下書きで、同じ行事に属し、情報元・タグは引き継ぐが確認日は空にする', function (): void {
    $event = makePublished(['2026-10-11', '2026-10-12']);
    $event->sources()->update(['checked_at' => '2026-10-01']);
    $event->tags()->attach(Tag::factory()->create(['name' => '獅子舞'])->id);

    $copy = $this->lifecycle->copyForNextYear($event->refresh(), $this->actor);

    expect($copy->id)->not->toBe($event->id)
        ->and($copy->series_id)->toBe($event->series_id)
        ->and($copy->is_published)->toBeFalse()
        ->and($copy->title)->toBe($event->title)
        ->and($copy->schedules->map(fn (EventSchedule $s): string => $s->date->toDateString())->all())->toBe(['2027-10-11', '2027-10-12'])
        ->and($copy->schedules->every(fn (EventSchedule $s): bool => ! $s->is_cancelled))->toBeTrue()
        ->and($copy->sources)->toHaveCount(1)
        ->and($copy->sources[0]->checked_at)->toBeNull()
        ->and($copy->tags->pluck('name')->all())->toBe(['獅子舞']);

    // もとの開催回は変わらない
    expect($event->refresh()->is_published)->toBeTrue()->and($event->schedules)->toHaveCount(2);
});

it('終わった開催回は「開催済み」になり、今日が最終日の開催回と、これからの開催回はそのまま', function (): void {
    $past = makePublished(['2026-10-10', '2026-10-11']);
    $today = makePublished(['2026-10-11', '2026-10-12']);
    $future = makePublished(['2026-10-12', '2026-10-30']);
    $cancelled = makePublished(['2026-10-01']);
    $this->lifecycle->cancelEvent($cancelled);

    $result = $this->lifecycle->finishEnded();

    expect($result['ended'])->toBe(1)
        ->and($past->refresh()->status)->toBe(EventStatus::Ended)
        ->and($today->refresh()->status)->toBe(EventStatus::Scheduled)
        ->and($future->refresh()->status)->toBe(EventStatus::Scheduled)
        ->and($cancelled->refresh()->status)->toBe(EventStatus::Cancelled);

    // 日本時間で判定する(UTC 15:00 = 日本時間の翌日 0:00)
    Carbon::setTestNow(Carbon::parse('2026-10-12 15:00:00', 'UTC'));
    expect($this->lifecycle->finishEnded()['ended'])->toBe(1)->and($today->refresh()->status)->toBe(EventStatus::Ended);
});

it('毎年開催の行事は、終わると「次回未定」の下書きができ、二重にはできない。1回だけの行事にはできない', function (): void {
    $yearly = makePublished(['2026-10-10'], ['recurrence' => Recurrence::Yearly]);
    $once = makePublished(['2026-10-10'], ['recurrence' => Recurrence::Once]);

    $first = $this->lifecycle->finishEnded();
    expect($first)->toBe(['ended' => 2, 'drafts' => 1]);

    $draft = Event::query()->where('series_id', $yearly->series_id)->where('status', EventStatus::Undecided->value)->firstOrFail();
    expect($draft->is_published)->toBeFalse()->and($draft->title)->toBe($yearly->title)->and($draft->schedules)->toHaveCount(0)
        ->and(Event::query()->where('series_id', $once->series_id)->count())->toBe(1);

    // もう一度動かしても増えない
    $yearly->update(['status' => EventStatus::Scheduled]);
    $second = $this->lifecycle->finishEnded();
    expect(Event::query()->where('series_id', $yearly->series_id)->where('status', EventStatus::Undecided->value)->count())->toBe(1)
        ->and($second['drafts'])->toBe(0);
});

it('これより新しい開催回(来年分のコピー)がすでにあれば、次回未定の下書きは作らない', function (): void {
    $event = makePublished(['2026-10-10'], ['recurrence' => Recurrence::Yearly]);
    $this->lifecycle->copyForNextYear($event, $this->actor);

    $result = $this->lifecycle->finishEnded();

    expect($result['ended'])->toBe(1)->and($result['drafts'])->toBe(0)
        ->and(Event::query()->where('series_id', $event->series_id)->count())->toBe(2);
});
