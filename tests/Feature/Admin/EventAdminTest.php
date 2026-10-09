<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\CategoryTarget;
use App\Enums\EventStatus;
use App\Enums\Recurrence;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00', 'Asia/Tokyo'));
    $this->admin = User::factory()->admin()->twoFactor()->create();
    $this->editor = User::factory()->twoFactor()->create(['role' => 'editor']);
    $this->region = Region::factory()->create(['name' => '高松市']);
    $this->category = Category::factory()->create(['target' => CategoryTarget::Event, 'name' => '祭り・行事']);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function validEventPayload(Region $region, EventSeries $series, array $override = []): array
{
    return array_merge([
        'series_id' => $series->id, 'title' => '[集落名]の獅子舞奉納', 'region_id' => $region->id, 'body' => '八幡神社に獅子舞を奉納します。',
        'venue_name' => '[集落名] 八幡神社', 'state' => 'draft', 'tags' => '獅子舞, 秋祭り',
        'schedules' => [['date' => '2026-10-17', 'start_time' => '18:00', 'end_time' => '21:00', 'note' => '宵宮'], ['date' => '2026-10-18', 'start_time' => '09:00', 'end_time' => '15:00', 'note' => '本宮']],
        'sources' => [['kind' => 'url', 'url' => 'https://example.com/news', 'title' => '自治会のお知らせ', 'checked_at' => '2026-10-06']],
    ], $override);
}

it('管理者でない人は、コンテンツの管理画面に入れない。編集者は入れるが、マスタは使えない', function (): void {
    $member = User::factory()->create();
    foreach (['/admin/events', '/admin/contents', '/admin/masters'] as $url) {
        $this->actingAs($member)->get($url)->assertNotFound();
    }

    foreach (['/admin/events', '/admin/contents', '/admin/series/create'] as $url) {
        $this->actingAsVerifiedAdmin($this->editor)->get($url)->assertOk();
    }
    $this->actingAsVerifiedAdmin($this->editor)->get('/admin/masters')->assertForbidden();
    $this->actingAsVerifiedAdmin($this->editor)->post('/admin/masters/tags', ['name' => 'x'])->assertForbidden();
});

it('行事を作り、開催回を日程・情報元・タグつきで登録できる(履歴に残る)', function (): void {
    $this->actingAsVerifiedAdmin($this->admin);

    $this->post('/admin/series', ['title' => '[集落名]の獅子舞奉納', 'region_id' => $this->region->id, 'recurrence' => 'yearly', 'category_id' => $this->category->id])
        ->assertRedirect();
    $series = EventSeries::query()->firstOrFail();
    expect($series->recurrence)->toBe(Recurrence::Yearly);

    $this->post('/admin/events', validEventPayload($this->region, $series, ['state' => 'published']))->assertRedirect();

    $event = Event::query()->firstOrFail();
    expect($event->is_published)->toBeTrue()
        ->and($event->schedules)->toHaveCount(2)
        ->and($event->sources)->toHaveCount(1)
        ->and($event->tags->pluck('name')->sort()->values()->all())->toBe(['獅子舞', '秋祭り'])
        ->and($event->search_text)->toContain('獅子舞')
        ->and(Revision::query()->where('revisionable_type', 'event')->where('revisionable_id', $event->id)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::ContentCreate->value)->where('target_type', 'event')->exists())->toBeTrue();

    $this->get('/admin/events?series='.$series->id)->assertOk()->assertSee('[集落名]の獅子舞奉納')->assertSee('宵宮');
});

it('情報元がないイベントを公開しようとすると、理由を出して保存しない(管理画面から)', function (): void {
    $series = EventSeries::factory()->create(['region_id' => $this->region->id]);
    $this->actingAsVerifiedAdmin($this->admin);

    $this->post('/admin/events', validEventPayload($this->region, $series, ['state' => 'published', 'sources' => []]))
        ->assertSessionHasErrors(['sources' => '情報元が1件もないイベントは公開できません。情報元(Webページ・チラシ・現地確認)を1件以上追加してください。']);

    expect(Event::query()->count())->toBe(0);

    // 下書きなら、情報元なしでも保存できる
    $this->post('/admin/events', validEventPayload($this->region, $series, ['state' => 'draft', 'sources' => []]))->assertRedirect();
    expect(Event::query()->count())->toBe(1)->and(Event::query()->firstOrFail()->is_published)->toBeFalse();
});

it('Web ページの情報元には URL が要る', function (): void {
    $series = EventSeries::factory()->create(['region_id' => $this->region->id]);
    $this->actingAsVerifiedAdmin($this->admin);

    $this->post('/admin/events', validEventPayload($this->region, $series, ['sources' => [['kind' => 'url', 'url' => '', 'title' => 'ページ']]]))
        ->assertSessionHasErrors('sources');
});

it('入力が不正なら保存しない(日付・時刻・URL・緯度経度・存在しない地域)', function (array $override): void {
    $series = EventSeries::factory()->create(['region_id' => $this->region->id]);
    $this->actingAsVerifiedAdmin($this->admin);

    $this->post('/admin/events', validEventPayload($this->region, $series, $override))->assertSessionHasErrors();

    expect(Event::query()->count())->toBe(0);
})->with([
    '日付が不正' => [['schedules' => [['date' => '2026/13/40']]]],
    '時刻が不正' => [['schedules' => [['date' => '2026-10-17', 'start_time' => '25:00']]]],
    'URL が不正' => [['url' => 'javascript:alert(1)']],
    '緯度が範囲外' => [['lat' => '123']],
    '地域がない' => [['region_id' => 999999]],
    'タイトルが空' => [['title' => '']],
    '情報元の URL が不正' => [['sources' => [['kind' => 'url', 'url' => 'not a url']]]],
]);

it('編集すると履歴が増え、履歴の画面から古い版に戻せる(戻したことも履歴に残る)', function (): void {
    $series = EventSeries::factory()->create(['region_id' => $this->region->id]);
    $this->actingAsVerifiedAdmin($this->admin);
    $this->post('/admin/events', validEventPayload($this->region, $series));
    $event = Event::query()->firstOrFail();

    $this->put('/admin/events/'.$event->id, validEventPayload($this->region, $series, ['title' => '獅子舞奉納(時間変更)', 'reason' => '主催者の連絡で時間を変更']))->assertRedirect();
    expect($event->refresh()->title)->toBe('獅子舞奉納(時間変更)');

    $page = $this->get('/admin/revisions/event/'.$event->id)->assertOk()->assertSee('主催者の連絡で時間を変更')->assertSee('title');
    $update = Revision::query()->where('revisionable_id', $event->id)->where('reason', '主催者の連絡で時間を変更')->firstOrFail();

    $this->post('/admin/revisions/'.$update->id.'/rollback')->assertRedirect()->assertSessionHas('status');

    expect($event->refresh()->title)->toBe('[集落名]の獅子舞奉納')
        ->and(Revision::query()->where('revisionable_type', 'event')->where('revisionable_id', $event->id)->count())->toBe(3)
        ->and(Revision::query()->where('revisionable_id', $event->id)->latest('id')->firstOrFail()->cause->value)->toBe('rollback')
        ->and(AuditLog::query()->where('action', AuditAction::ContentRollback->value)->exists())->toBeTrue();
});

it('編集すると、日付を変えた公開中のイベントは「延期」になる', function (): void {
    $series = EventSeries::factory()->create(['region_id' => $this->region->id]);
    $this->actingAsVerifiedAdmin($this->admin);
    $this->post('/admin/events', validEventPayload($this->region, $series, ['state' => 'published']));
    $event = Event::query()->firstOrFail();

    $payload = validEventPayload($this->region, $series, ['state' => 'published']);
    $payload['schedules'][0]['date'] = '2026-10-24';
    $this->put('/admin/events/'.$event->id, $payload);

    expect($event->refresh()->displayStatus())->toBe('postponed');
});

it('中止にした日は、消えずに「中止」と表示される。開催回ごとの中止、中止の取り消しもできる', function (): void {
    $series = EventSeries::factory()->create(['region_id' => $this->region->id]);
    $this->actingAsVerifiedAdmin($this->admin);
    $this->post('/admin/events', validEventPayload($this->region, $series, ['state' => 'published']));
    $event = Event::query()->firstOrFail();
    $first = $event->schedules()->firstOrFail();

    $this->post('/admin/schedules/'.$first->id.'/cancel')->assertRedirect();
    expect($first->refresh()->is_cancelled)->toBeTrue()->and($event->schedules)->toHaveCount(2);
    $this->get('/admin/events?series='.$series->id)->assertSee('中止を取り消す');

    $this->post('/admin/schedules/'.$first->id.'/restore')->assertRedirect();
    expect($first->refresh()->is_cancelled)->toBeFalse();

    $this->post('/admin/events/'.$event->id.'/cancel')->assertRedirect();
    expect($event->refresh()->status)->toBe(EventStatus::Cancelled)->and($event->is_published)->toBeTrue();
});

it('去年の回をコピーして来年分を作ると、編集画面に進む(公開前の下書き)', function (): void {
    $series = EventSeries::factory()->create(['region_id' => $this->region->id]);
    $this->actingAsVerifiedAdmin($this->admin);
    $this->post('/admin/events', validEventPayload($this->region, $series, ['state' => 'published']));
    $event = Event::query()->firstOrFail();

    $response = $this->post('/admin/events/'.$event->id.'/copy');

    $copy = Event::query()->whereKeyNot($event->id)->firstOrFail();
    $response->assertRedirect(route('admin.events.edit', $copy));
    expect($copy->is_published)->toBeFalse()
        ->and($copy->schedules->pluck('date')->map(fn ($d) => $d->toDateString())->all())->toBe(['2027-10-17', '2027-10-18']);
});

it('誤登録として削除すると、論理削除され(履歴は残る)、公開は止まる', function (): void {
    $series = EventSeries::factory()->create(['region_id' => $this->region->id]);
    $this->actingAsVerifiedAdmin($this->admin);
    $this->post('/admin/events', validEventPayload($this->region, $series, ['state' => 'published']));
    $event = Event::query()->firstOrFail();

    $this->delete('/admin/events/'.$event->id)->assertRedirect();

    expect(Event::query()->count())->toBe(0)
        ->and(Event::withTrashed()->count())->toBe(1)
        ->and(Event::withTrashed()->firstOrFail()->is_published)->toBeFalse();
    $this->get('/admin/revisions/event/'.$event->id)->assertOk();
});

it('一覧は、行事名・地域で探せて、「来年分なし」「非公開あり」で絞り込める', function (): void {
    $this->actingAsVerifiedAdmin($this->admin);
    $kagawa = EventSeries::factory()->create(['title' => '獅子舞奉納', 'region_id' => $this->region->id, 'recurrence' => Recurrence::Yearly]);
    $other = EventSeries::factory()->create(['title' => '朝市', 'region_id' => Region::factory()->create(['name' => '直島町'])->id, 'recurrence' => Recurrence::Yearly]);
    $upcoming = Event::factory()->create(['series_id' => $kagawa->id]);
    $upcoming->schedules()->create(['date' => '2026-10-20']);
    $old = Event::factory()->create(['series_id' => $other->id]);
    $old->schedules()->create(['date' => '2025-10-01']);

    $this->get('/admin/events?q=獅子')->assertSee('獅子舞奉納')->assertDontSee('朝市');
    $this->get('/admin/events?q=直島')->assertSee('朝市')->assertDontSee('獅子舞奉納');
    $this->get('/admin/events?filter=upcoming')->assertSee('獅子舞奉納')->assertDontSee('朝市');
    $this->get('/admin/events?filter=no_next')->assertSee('朝市')->assertDontSee('獅子舞奉納');
    $this->get('/admin/events?filter=unpublished')->assertSee('獅子舞奉納')->assertSee('朝市');
});

it('すべての編集画面が開く(新規・編集)', function (): void {
    $series = EventSeries::factory()->create(['region_id' => $this->region->id]);
    $event = Event::factory()->published()->onDate('2026-10-20')->create(['series_id' => $series->id]);
    $this->actingAsVerifiedAdmin($this->admin);

    $this->get('/admin/series/create')->assertOk()->assertSee('行事を追加する');
    $this->get('/admin/series/'.$series->id.'/edit')->assertOk()->assertSee($series->title);
    $this->get('/admin/events/create?series='.$series->id)->assertOk()->assertSee('情報元')->assertSee('開催回');
    $this->get('/admin/events/'.$event->id.'/edit')->assertOk()->assertSee($event->title)->assertSee('自治会のお知らせ')->assertSee('2026-10-20');
    $this->get('/admin/spots/create')->assertOk();
    $this->get('/admin/articles/create')->assertOk();
    $this->get('/admin/masters?tab=ng')->assertOk();
});

it('「延期」は、管理者が切り替えたときだけ反映する(変えていない欄で自動の判定を消さない)', function (): void {
    $series = EventSeries::factory()->create(['region_id' => $this->region->id]);
    $this->actingAsVerifiedAdmin($this->admin);
    $this->post('/admin/events', validEventPayload($this->region, $series, ['state' => 'published']));
    $event = Event::query()->firstOrFail();

    // 管理者が「延期として表示する」を付けた
    $this->put('/admin/events/'.$event->id, validEventPayload($this->region, $series, ['state' => 'published', 'is_postponed' => '1']));
    expect($event->refresh()->is_postponed)->toBeTrue();

    // そのまま保存しても、延期のまま
    $this->put('/admin/events/'.$event->id, validEventPayload($this->region, $series, ['state' => 'published', 'is_postponed' => '1']));
    expect($event->refresh()->is_postponed)->toBeTrue();

    // 外した
    $this->put('/admin/events/'.$event->id, validEventPayload($this->region, $series, ['state' => 'published']));
    expect($event->refresh()->is_postponed)->toBeFalse();
});
