<?php

declare(strict_types=1);

use App\Contracts\Notifier;
use App\Enums\ConsentStatus;
use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\TakedownConsent;
use App\Models\User;
use App\Services\Setting\AppMetaService;
use App\Services\Submission\SubmissionPruner;
use App\Services\Update\CronHealth;
use App\Services\Update\CronWatcher;
use Illuminate\Console\Scheduling\Schedule;
use Tests\Support\FakeNotifier;

/** 設計書10.2の定期処理の一覧(名前 => cron 式)。増やす・変えるときは、設計書と一緒にこの表も直す */
const EXPECTED_SCHEDULE = [
    'cron-heartbeat' => '* * * * *',
    'queue-work' => '* * * * *',
    'pageviews-flush' => '0 * * * *',
    'popularity-calculate' => '0 * * * *',
    'events-finish' => '0 * * * *',
    'submissions-prune' => '10 4 * * *',
    'logs-prune' => '20 4 * * *',
    'ai-resume' => '0 * * * *',
    'update-recalculate-window' => '30 3 * * 1',
    'crawl-run' => '0 * * * *',
    'update-run' => '0 * * * *',
    'update-recover' => '*/5 * * * *',
    'regions-generate' => '*/10 * * * *',
    'ai-models-refresh' => '40 3 * * *',
    'holidays-import' => '10 3 * * 0',
    'geo-import' => '20 3 * * 0',
    'takedown-deadlines' => '0 9 * * *',
];

it('設計書10.2の定期処理がすべて登録されている(名前と頻度)', function (): void {
    $actual = [];
    foreach (app(Schedule::class)->events() as $event) {
        $actual[(string) $event->description] = $event->expression;
    }

    expect($actual)->toEqualCanonicalizing(EXPECTED_SCHEDULE);
});

it('登録された定期処理のコマンドは、すべて実在する', function (): void {
    $commands = array_keys(Artisan::all());
    foreach (app(Schedule::class)->events() as $event) {
        if ($event->command === null) {
            continue;
        }
        preg_match('/artisan[\'"]?\s+([a-z:\-]+)/', $event->command, $m);
        expect($commands)->toContain($m[1] ?? '');
    }
});

it('queue:work は優先度の順(high → ai-2 → ai-3 → ai-4 → ai-5 → low)で、1分以内に終わる', function (): void {
    $event = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'queue-work');

    expect($event->command)->toContain('--queue=high,ai-2,ai-3,ai-4,ai-5,low')->toContain('--max-time=50')->and($event->withoutOverlapping)->toBeTrue();
});

function cronWorld(): FakeNotifier
{
    config(['app.cron_watch' => true]);
    app(AppMetaService::class)->markInstalled();
    $notifier = new FakeNotifier;
    app()->instance(Notifier::class, $notifier);

    return $notifier;
}

it('cron が止まったら Discord に1回だけ知らせ、動き出したら「再開」を1回だけ知らせる', function (): void {
    $notifier = cronWorld();
    app(CronHealth::class)->beat();

    expect(app(CronWatcher::class)->check())->toBeNull();

    $this->travel(6)->minutes();
    expect(app(CronWatcher::class)->check())->toBe('stopped')->and($notifier->messages)->toHaveCount(1)->and($notifier->messages[0])->toContain('止まっています');
    // 同じ停止では、もう知らせない
    $this->travel(10)->minutes();
    expect(app(CronWatcher::class)->check())->toBeNull()->and($notifier->messages)->toHaveCount(1);

    app(CronHealth::class)->beat();
    expect(app(CronWatcher::class)->check())->toBe('recovered')->and($notifier->messages)->toHaveCount(2)->and($notifier->messages[1])->toContain('再開');
    expect(app(CronWatcher::class)->check())->toBeNull()->and($notifier->messages)->toHaveCount(2);

    // また止まれば、もう一度知らせる
    $this->travel(6)->minutes();
    expect(app(CronWatcher::class)->check())->toBe('stopped');
    $this->travelBack();
});

it('一度も動いていない cron も止まっているとみなして知らせる。設置前は何もしない', function (): void {
    config(['app.cron_watch' => true]);
    $notifier = new FakeNotifier;
    app()->instance(Notifier::class, $notifier);
    expect(app(CronWatcher::class)->check())->toBeNull();

    app(AppMetaService::class)->markInstalled();
    expect(app(CronWatcher::class)->check())->toBe('stopped')->and($notifier->messages[0])->toContain('一度も');
});

it('Web へのアクセスのついでに確かめる(1分に1回まで)。管理画面の警告も出る', function (): void {
    $notifier = cronWorld();
    Cache::forget('cron-watch:throttle');

    $this->get('/terms/')->assertOk();
    $this->get('/terms/')->assertOk();

    expect($notifier->messages)->toHaveCount(1);
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());
    $this->get('/admin/')->assertOk()->assertSee('定期処理');
});

it('cron の通知に、個人情報や秘密の値を入れない', function (): void {
    $notifier = cronWorld();
    app(CronWatcher::class)->check();

    expect($notifier->messages[0])->not->toMatch('/https?:\/\/|@/');
});

it('削除への同意の照会の期限が過ぎたら、受付番号だけを管理者に知らせる(1回だけ)。自動では消さない', function (): void {
    $notifier = new FakeNotifier;
    app()->instance(Notifier::class, $notifier);
    $member = User::factory()->create();
    $inquiry = Inquiry::query()->create(['receipt_no' => '20261010-EEEEEE', 'kind' => 'takedown', 'body' => '本文は通知しない', 'email' => 'a@example.com']);
    TakedownConsent::query()->create(['inquiry_id' => $inquiry->id, 'user_id' => $member->id, 'status' => ConsentStatus::Pending, 'deadline_at' => now()->subDay()]);

    $this->artisan('takedown:deadlines')->assertSuccessful();
    $this->artisan('takedown:deadlines')->assertSuccessful();

    expect($notifier->messages)->toHaveCount(1)->and($notifier->messages[0])->toContain('20261010-EEEEEE')->not->toContain('本文')->not->toContain('a@example.com')
        ->and($inquiry->refresh()->result)->toBeNull();
});

it('対応が終わって保持期間(3年)を過ぎたお問い合わせは消え、IPハッシュは90日で消える', function (): void {
    $old = Inquiry::query()->create(['receipt_no' => '20231010-OLDOLD', 'kind' => 'general', 'body' => 'x', 'status' => InquiryStatus::Done, 'handled_at' => now()->subYears(4), 'ip_hash' => 'h']);
    $recentDone = Inquiry::query()->create(['receipt_no' => '20261001-DONEDN', 'kind' => 'general', 'body' => 'x', 'status' => InquiryStatus::Done, 'handled_at' => now()->subYear()]);
    $open = Inquiry::query()->create(['receipt_no' => '20200101-OPENOP', 'kind' => 'general', 'body' => 'x']);
    $open->forceFill(['created_at' => now()->subDays(100), 'ip_hash' => 'h2'])->saveQuietly();

    $result = app(SubmissionPruner::class)->prune();

    expect(Inquiry::query()->whereKey($old->id)->exists())->toBeFalse()->and(Inquiry::query()->whereKey($recentDone->id)->exists())->toBeTrue()
        ->and(Inquiry::query()->whereKey($open->id)->exists())->toBeTrue()->and($open->refresh()->ip_hash)->toBeNull()->and($result['inquiries'])->toBe(1);
});
