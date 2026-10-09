<?php

declare(strict_types=1);

use App\Models\PageViewHour;
use App\Models\User;
use App\Services\Analytics\PageViewCounter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

function viewsInHour(string $jst): int
{
    return (int) PageViewHour::query()->where('hour', CarbonImmutable::parse($jst, 'Asia/Tokyo'))->value('count');
}

beforeEach(function (): void {
    Cache::flush();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-12 13:20:00', 'Asia/Tokyo'));
    Carbon::setTestNow(Carbon::parse('2026-10-12 13:20:00', 'Asia/Tokyo'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

it('公開ページの閲覧を数え、1時間ごとのジョブで終わった時間だけ移す', function (): void {
    $counter = app(PageViewCounter::class);
    $counter->record();
    $counter->record();

    // いまの時間(13時台)はまだ移さない
    expect($counter->flush())->toBe(0);

    Carbon::setTestNow(Carbon::parse('2026-10-12 14:05:00', 'Asia/Tokyo'));
    expect($counter->flush())->toBe(1)->and(viewsInHour('2026-10-12 13:00:00'))->toBe(2);

    // 二重には足されない
    expect($counter->flush())->toBe(0)->and(viewsInHour('2026-10-12 13:00:00'))->toBe(2);
});

it('同じ時間の分を別々に移しても、合計になる', function (): void {
    $counter = app(PageViewCounter::class);
    $counter->record();
    Carbon::setTestNow(Carbon::parse('2026-10-12 14:05:00', 'Asia/Tokyo'));
    $counter->flush();

    $counter->record(Carbon::parse('2026-10-12 13:50:00', 'Asia/Tokyo')); // 遅れて届いた分
    $counter->flush();

    expect(viewsInHour('2026-10-12 13:00:00'))->toBe(2);
});

it('90日を過ぎた集計は消える', function (): void {
    PageViewHour::query()->create(['hour' => now()->subDays(91), 'count' => 5]);
    PageViewHour::query()->create(['hour' => now()->subDays(10), 'count' => 5]);

    expect(app(PageViewCounter::class)->prune())->toBe(1)->and(PageViewHour::query()->count())->toBe(1);
});

it('ミドルウェアは、公開ページの GET だけを数える', function (): void {
    Route::middleware('web')->get('/__public', fn () => 'ok');
    Route::middleware('web')->post('/__post', fn () => 'ok');
    Route::middleware('web')->get('/__missing', fn () => abort(404));
    Route::middleware('web')->get('/__redirect', fn () => redirect('/'));

    $this->get('/__public')->assertOk();
    $this->post('/__post'); // GET 以外は数えない
    $this->get('/__missing')->assertNotFound();
    $this->get('/__redirect')->assertRedirect();

    Carbon::setTestNow(Carbon::parse('2026-10-12 14:05:00', 'Asia/Tokyo'));
    app(PageViewCounter::class)->flush();

    expect(viewsInHour('2026-10-12 13:00:00'))->toBe(1);
});

it('管理者の閲覧は数えない', function (): void {
    Route::middleware('web')->get('/__public', fn () => 'ok');
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($admin)->get('/__public')->assertOk();
    $this->actingAs($member)->get('/__public')->assertOk();

    Carbon::setTestNow(Carbon::parse('2026-10-12 14:05:00', 'Asia/Tokyo'));
    app(PageViewCounter::class)->flush();

    expect(viewsInHour('2026-10-12 13:00:00'))->toBe(1);
});

it('ボットは数えない', function (): void {
    Route::middleware('web')->get('/__public', fn () => 'ok');

    foreach (['Googlebot/2.1', 'DoinakaBot/1.0', 'Mozilla/5.0 (compatible; bingbot/2.0)', 'curl/8.0', 'facebookexternalhit/1.1'] as $agent) {
        $this->withHeader('User-Agent', $agent)->get('/__public')->assertOk();
    }
    $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Safari/605.1')->get('/__public')->assertOk();

    Carbon::setTestNow(Carbon::parse('2026-10-12 14:05:00', 'Asia/Tokyo'));
    app(PageViewCounter::class)->flush();

    expect(viewsInHour('2026-10-12 13:00:00'))->toBe(1);
});

it('管理画面・インストーラー・API は数えない', function (): void {
    $admin = User::factory()->admin()->create();
    Route::middleware('web')->get('/api/v1/__x', fn () => 'ok');

    $this->get('/up');
    $this->get('/api/v1/__x');
    $this->actingAs($admin)->get('/admin');

    Carbon::setTestNow(Carbon::parse('2026-10-12 14:05:00', 'Asia/Tokyo'));
    app(PageViewCounter::class)->flush();

    expect(PageViewHour::query()->count())->toBe(0);
});
