<?php

declare(strict_types=1);

use App\Contracts\Notifier;
use App\Enums\AppMetaKey;
use App\Enums\CronMode;
use App\Enums\SettingKey;
use App\Jobs\RunCrawlSource;
use App\Models\User;
use App\Services\Crawl\CrawlRunner;
use App\Services\Cron\WebCronBudget;
use App\Services\Cron\WebCronRunner;
use App\Services\Cron\WebCronStatus;
use App\Services\Cron\WebCronTrigger;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use App\Services\Update\CronHealth;
use App\Services\Update\CronWatcher;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Support\FakeNotifier;

require_once __DIR__.'/../Ai/helpers.php';
require_once __DIR__.'/../Crawl/helpers.php';

/*
 * アクセスをきっかけに予約処理を動かす(WP-Cron と同じ考え方)。kagoya は apache2handler なので、応答のあとに処理を続けられない。
 * そのため、自分自身の署名つきの内部 URL を、待たずに呼ぶ。
 */

/** 内部 URL(署名つき)を、トリガーと同じやり方で作る(番号は、キャッシュに登録する) */
function cronUrl(string $kind = 'schedule', ?string $nonce = null, int $ttl = 120): string
{
    $nonce ??= Str::random(32);
    Cache::put('webcron:nonce:'.$nonce, true, 150);

    return URL::temporarySignedRoute('cron.run', now()->addSeconds($ttl), ['k' => $kind, 'n' => $nonce], absolute: false);
}

beforeEach(function (): void {
    config(['app.web_cron' => true]);
    app(AppMetaService::class)->markInstalled();
    Cache::flush();
    Carbon::setTestNow(Carbon::parse('2026-10-12 03:00:30', 'Asia/Tokyo'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    Cache::flush();
});

it('cron がなく、前回から60秒たっていれば、アクセスをきっかけに、署名つきの内部 URL を呼ぶ(待たない・短いタイムアウト)', function (): void {
    Http::fake(['*' => Http::response([], 202)]);

    $this->get('/terms/')->assertOk();

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'POST'
            && str_starts_with($request->url(), rtrim((string) config('app.url'), '/').'/cron/run?')
            && str_contains($request->url(), 'signature=') && str_contains($request->url(), 'expires=')
            && str_contains($request->url(), 'k=schedule')
            && $request->hasHeader('User-Agent', 'DoinakaWebCron/1');
    });
    expect(app(WebCronStatus::class)->mode())->toBe(CronMode::Web);
});

it('同じ1分の間は、何度アクセスがあっても、1回しか呼ばない', function (): void {
    Http::fake(['*' => Http::response([], 202)]);

    $this->get('/terms/');
    $this->get('/privacy/');
    $this->get('/about/');

    Http::assertSentCount(1);
});

it('前回の実行から60秒たっていなければ呼ばない(たっていれば呼ぶ)', function (): void {
    Http::fake(['*' => Http::response([], 202)]);
    app(CronHealth::class)->beat('web');

    $this->get('/terms/');
    Http::assertNothingSent();

    Carbon::setTestNow(now()->addSeconds(61));
    Cache::flush();
    $this->get('/terms/');
    Http::assertSentCount(1);
});

it('本物の cron が動いているとわかったら、アクセスで動かす方式は自動で止まる(cron が止まれば、また動く)', function (): void {
    Http::fake(['*' => Http::response([], 202)]);
    app(CronHealth::class)->beat('cli');

    expect(app(WebCronStatus::class)->mode())->toBe(CronMode::Cron);
    $this->get('/terms/');
    Http::assertNothingSent();
    expect(app(WebCronRunner::class)->run())->toBe(['skipped' => 'cron']);

    // cron が5分以上止まる → アクセスで動かす方式に戻る
    Carbon::setTestNow(now()->addMinutes(6));
    Cache::flush();
    expect(app(WebCronStatus::class)->mode())->toBe(CronMode::Web);
    $this->get('/terms/');
    Http::assertSentCount(1);
});

it('設定でオフにできる。オフで cron もなければ「動いていません」', function (): void {
    Http::fake(['*' => Http::response([], 202)]);
    app(SettingsService::class)->set(SettingKey::CronWebEnabled, false);

    $this->get('/terms/');

    Http::assertNothingSent();
    expect(app(WebCronStatus::class)->mode())->toBe(CronMode::Off)->and(app(WebCronRunner::class)->run())->toBe(['skipped' => 'off']);
});

it('ヘルスチェックのアクセスでは呼ばない。設定(WEB_CRON)で切ると、まったく呼ばない', function (): void {
    Http::fake(['*' => Http::response([], 202)]);

    $this->get('/up')->assertOk();
    Http::assertNothingSent();

    Cache::flush();
    config(['app.web_cron' => false]);
    $this->get('/terms/');
    Http::assertNothingSent();
});

it('呼ぶとき、タイムアウトは想定どおり(失敗に数えない)。つながらない・名前が引けないときは、失敗を数える', function (): void {
    Http::fake(['*' => Http::sequence()->pushFailedConnection('cURL error 28: Operation timed out after 500 milliseconds')->pushFailedConnection('cURL error 6: Could not resolve host: xn--gdkt37rmci.net')]);
    expect(app(WebCronTrigger::class)->fire())->toBeTrue();
    expect(app(WebCronStatus::class)->failures())->toBe(0);

    Cache::flush();
    expect(app(WebCronTrigger::class)->fire())->toBeFalse();
    expect(app(WebCronStatus::class)->failures())->toBe(1);
});

it('内部 URL: 署名がない・書き換えた・期限切れ・こちらが作っていない番号・使い回しは、すべて 403', function (): void {
    $this->post('/cron/run')->assertForbidden();
    $this->post('/cron/run?k=schedule&n=abc')->assertForbidden();

    $url = cronUrl();
    $this->post($url.'x')->assertForbidden();
    $this->post(str_replace('k=schedule', 'k=long', $url))->assertForbidden();

    // 署名は正しいが、こちらが作った番号ではない
    $forged = URL::temporarySignedRoute('cron.run', now()->addMinute(), ['k' => 'schedule', 'n' => 'not-issued'], absolute: false);
    $this->post($forged)->assertForbidden();

    // 期限切れ
    $old = cronUrl('schedule', null, 30);
    Carbon::setTestNow(now()->addSeconds(60));
    $this->post($old)->assertForbidden();
    Carbon::setTestNow(Carbon::parse('2026-10-12 03:00:30', 'Asia/Tokyo'));

    // 1回きり
    $once = cronUrl();
    $this->post($once)->assertStatus(202);
    $this->post($once)->assertForbidden();
});

it('内部 URL は GET では開けない。セッションも Cookie も使わない', function (): void {
    // 許されないメソッド(404 か 405)。実行はされない
    expect(in_array($this->get(cronUrl())->status(), [404, 405], true))->toBeTrue();
    expect(app(WebCronStatus::class)->lastWebRun())->toBeNull();

    $response = $this->post(cronUrl());
    expect($response->headers->getCookies())->toBe([]);
});

it('海外の IP の制限と、公開前モードの門の対象外(自分自身を呼べる)', function (): void {
    app(SettingsService::class)->set(SettingKey::GeoBlockOverseas, true);
    app(SettingsService::class)->set(SettingKey::SitePrelaunch, true);

    $this->call('POST', cronUrl(), [], [], [], ['REMOTE_ADDR' => '8.8.8.8'])->assertStatus(202);
});

it('実行: 時刻になった予約を、同じプロセスの中で動かし、最後の実行の様子を残す。キューとアップデートは、ここでは動かさない', function (): void {
    $result = app(WebCronRunner::class)->run();

    expect($result['ran'])->toContain('cron-heartbeat')->not->toContain('queue-work')->not->toContain('update-run')
        ->and($result['errors'])->toBe([])
        ->and($result['seconds'])->toBeLessThan(WebCronRunner::LIMIT_SECONDS + 5);
    $health = app(CronHealth::class);
    // アクセスで動かした印(cron が動いた印ではない)
    expect($health->lastWebRun())->not->toBeNull()->and($health->lastCliRun())->toBeNull()->and(app(WebCronStatus::class)->mode())->toBe(CronMode::Web)
        ->and(app(WebCronStatus::class)->lastResult()['ran'])->toContain('cron-heartbeat');
});

it('実行: 同時には1つだけ(ロック)。前回から60秒たっていなければ動かさない', function (): void {
    $lock = Cache::lock('webcron:run', 60);
    $lock->get();
    expect(app(WebCronRunner::class)->run())->toBe(['skipped' => 'busy']);
    $lock->release();

    app(WebCronRunner::class)->run();
    expect(app(WebCronRunner::class)->run())->toBe(['skipped' => 'too_soon']);
});

it('実行: キューのジョブを、残りの時間の中で処理する', function (): void {
    config(['queue.default' => 'database']);
    dispatch(function (): void {
        Cache::forever('webcron-test:ran', true);
    })->onQueue('low');

    app(WebCronRunner::class)->run();

    expect(Cache::get('webcron-test:ran'))->toBeTrue();
});

it('実行: 時間の上限(25秒)の予算が、実行の間だけ有効で、終わると外れる', function (): void {
    $budget = app(WebCronBudget::class);
    expect($budget->active())->toBeFalse()->and($budget->remaining())->toBeNull();

    $budget->start(25);
    expect($budget->active())->toBeTrue()->and($budget->remaining())->toBe(25);
    Carbon::setTestNow(now()->addSeconds(30));
    expect($budget->remaining())->toBe(0);
    $budget->stop();

    app(WebCronRunner::class)->run();
    expect($budget->active())->toBeFalse();
});

it('長い処理(自動アップデート)は、別のリクエスト(long)。時刻になっていなければ動かさない。時刻になっていれば動かす', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 12:15:00', 'Asia/Tokyo'));
    expect(app(WebCronRunner::class)->runLong())->toBe(['skipped' => 'not_due']);

    $this->post(cronUrl('long'))->assertStatus(202)->assertJson(['skipped' => 'not_due']);

    // 更新の時間帯のはじめ(日本時間の正時)になれば、動かす(GitHub への確認は、つながらない扱いにする)
    Http::fake(['*' => Http::response('', 500)]);
    Carbon::setTestNow(Carbon::parse('2026-10-12 04:00:10', 'Asia/Tokyo'));
    app(AppMetaService::class)->set(AppMetaKey::UpdateWindowHour, '4');
    expect(app(WebCronRunner::class)->runLong())->toBe(['ran' => ['update-run']]);
});

it('巡回: 時間の上限が近いと、ページの途中で区切って、続きを次の実行に回す。続きから読んで終わる', function (): void {
    fakeDns();
    Storage::fake('local');
    $source = crawlSource();
    fakeSite(['https://city.example/events/' => listHtml(['/events/1']), 'https://city.example/events/1' => detailHtml('丸亀の秋祭り')]);
    useAi([['events' => [crawledEvent()]], ['events' => []]]);

    // 上限まで残り10秒(余裕の15秒より近い)→ ページを1つも読まずに区切る。失敗にも成功にも数えない
    $run = app(CrawlRunner::class)->run($source, CarbonImmutable::now()->addSeconds(10));
    expect($run->status)->toBe('deferred')->and($run->error)->toBe('time_budget')->and($run->pages_fetched)->toBe(0)
        ->and($source->refresh()->failure_streak)->toBe(0)->and($source->isPaused())->toBeFalse();

    // 次の実行(上限なし)で、続きから読んで終わる
    $run = app(CrawlRunner::class)->run($source);
    expect($run->status)->toBe('ok')->and($run->pages_fetched)->toBe(2);
});

it('巡回のジョブ: 区切ったときは、同じジョブをもう一度キューに入れる(待ち時間つき)', function (): void {
    fakeDns();
    Storage::fake('local');
    $source = crawlSource();
    fakeSite(['https://city.example/events/' => listHtml(['/events/1']), 'https://city.example/events/1' => detailHtml('x')]);
    useAi([['events' => []]]);
    Queue::fake();

    app(WebCronBudget::class)->start(10);
    (new RunCrawlSource($source->id))->handle(app(CrawlRunner::class), app(WebCronBudget::class));

    Queue::assertPushed(RunCrawlSource::class, fn (RunCrawlSource $job): bool => $job->sourceId === $source->id && $job->delay !== null);
    app(WebCronBudget::class)->stop();
});

it('cron の停止の通知: アクセスで動かしているときは「止まった」とは言わない。自分自身を呼べない状態が続いたときだけ、1回知らせる', function (): void {
    config(['app.cron_watch' => true]);
    $notifier = new FakeNotifier;
    app()->instance(Notifier::class, $notifier);

    // 最後に動いたのが1時間前でも、アクセスで動かしているので、通知しない
    app(CronHealth::class)->beat('web');
    Carbon::setTestNow(now()->addHour());
    expect(app(CronWatcher::class)->check())->toBeNull()->and($notifier->messages)->toBe([]);

    // 自分自身を呼べない状態が5回続くと、1回だけ知らせる
    app(AppMetaService::class)->set(AppMetaKey::WebCronFailures, '5');
    expect(app(CronWatcher::class)->check())->toBe('stopped')->and($notifier->messages)->toHaveCount(1)->and($notifier->messages[0])->toContain('自分自身');
    expect(app(CronWatcher::class)->check())->toBeNull()->and($notifier->messages)->toHaveCount(1);

    app(AppMetaService::class)->set(AppMetaKey::WebCronFailures, '0');
    expect(app(CronWatcher::class)->check())->toBe('recovered');
});

it('管理画面: ダッシュボードと設定「予約処理」に、動かし方と最後の実行の時刻が出る。アクセスが少ないと遅れることも書いてある', function (): void {
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());

    $this->get('/admin/')->assertOk()->assertSee('予約処理の動かし方')->assertSee('アクセスで動かす')->assertSee('まだ動いていません')->assertSee('アクセスが少ないと、処理が遅れます');

    app(WebCronRunner::class)->run();
    $this->get('/admin/settings/cron')->assertOk()->assertSee('予約処理をアクセスで動かす')->assertSee('2026-10-12 03:00:30')->assertSee('動かした予約')->assertSee('自動アップデートは');

    app(CronHealth::class)->beat('cli');
    $this->get('/admin/')->assertOk()->assertSee('サーバーの cron');
});

it('設定の「予約処理」をオフにすると保存され、操作ログに残る', function (): void {
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());

    $this->post('/admin/settings/cron', ['cron__web_enabled' => '0'])->assertRedirect('/admin/settings/cron');

    expect(app(SettingsService::class)->bool(SettingKey::CronWebEnabled))->toBeFalse();
});
