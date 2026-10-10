<?php

declare(strict_types=1);

use App\Contracts\Notifier;
use App\Contracts\ReleaseSource;
use App\Enums\AuditAction;
use App\Enums\SettingKey;
use App\Enums\UpdateStatus;
use App\Enums\UpdateTrigger;
use App\Models\AuditLog;
use App\Models\UpdateRun;
use App\Models\User;
use App\Services\Setting\SettingsService;
use App\Services\Update\AutoUpdater;
use App\Services\Update\CronHealth;
use App\Services\Update\ReleaseInfo;
use App\Services\Update\Version;
use Tests\Support\FakeNotifier;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->twoFactor()->create(['name' => '管理者さん']);
});

it('管理者でない人は、更新の画面も操作も使えない(会員には管理画面があることも見せない)', function (): void {
    $member = User::factory()->create();

    $requests = [
        ['get', '/admin'], ['get', '/admin/update'], ['post', '/admin/update/check'], ['post', '/admin/update/apply'],
        ['post', '/admin/update/settings'], ['post', '/admin/update/webhook'], ['post', '/admin/update/webhook/test'],
    ];

    // 未ログインは管理画面のログインへ
    foreach ($requests as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('admin.login'));
    }
    // 会員は 404
    foreach ($requests as [$method, $url]) {
        $this->actingAs($member)->{$method}($url)->assertNotFound();
    }

    expect(UpdateRun::query()->count())->toBe(0);
});

it('編集者は管理画面には入れるが、更新と設定は使えない(権限がない)', function (): void {
    $editor = User::factory()->twoFactor()->create(['role' => 'editor']);

    $this->actingAsVerifiedAdmin($editor)->get('/admin')->assertOk();
    $this->actingAsVerifiedAdmin($editor)->get('/admin/update')->assertForbidden();
    $this->actingAsVerifiedAdmin($editor)->post('/admin/update/apply')->assertForbidden();
});

it('2段階認証を通っていない管理者は、更新の画面に入れない', function (): void {
    $this->actingAs($this->admin)->get('/admin/update')->assertRedirect(route('admin.two-factor'));
});
it('管理者は更新の画面を開ける', function (): void {
    UpdateRun::query()->create([
        'version_from' => 'v26.9.3', 'version_to' => 'v26.10.1', 'trigger' => UpdateTrigger::Auto, 'status' => UpdateStatus::Success,
        'started_at' => now()->subSeconds(52), 'finished_at' => now(),
    ]);
    UpdateRun::query()->create([
        'version_from' => 'v26.9.2', 'version_to' => 'v26.9.3', 'trigger' => UpdateTrigger::Manual, 'triggered_by' => 'shikoku_taro',
        'status' => UpdateStatus::RolledBack, 'log' => "…\n動作確認で検索ページが500", 'started_at' => now()->subDays(2),
    ]);

    $this->actingAsVerifiedAdmin($this->admin)->get('/admin/update')
        ->assertOk()
        ->assertSee('アップデート')
        ->assertSee('v26.9.3 → v26.10.1')
        ->assertSee('成功')
        ->assertSee('戻した')
        ->assertSee('手動(shikoku_taro)')
        ->assertSee('動作確認で検索ページが500')
        ->assertSee('1. バックアップ');
});

it('APP_DEBUG が true なら警告を出す', function (): void {
    config(['app.debug' => true]);

    $this->actingAsVerifiedAdmin($this->admin)->get('/admin/update')->assertSee('APP_DEBUG が true になっています');
});

it('cron が止まっているときは警告を出す', function (): void {
    $this->actingAsVerifiedAdmin($this->admin)->get('/admin')->assertSee('定期処理(cron)が止まっています');

    app(CronHealth::class)->beat();

    $this->actingAsVerifiedAdmin($this->admin)->get('/admin')->assertDontSee('定期処理(cron)が止まっています');
});

it('「今すぐ確認」は確認だけをして、適用はしない(自動更新が ON でも)', function (): void {
    app(SettingsService::class)->set(SettingKey::UpdateAuto, true);
    file_put_contents(base_path('VERSION'), "v26.10.1\n");
    $this->app->bind(ReleaseSource::class, fn () => new class implements ReleaseSource
    {
        public function releases(string $repository): array
        {
            return [new ReleaseInfo(new Version(26, 10, 3), 'v26.10.3', false, '地図のピンを直した', 'https://github.com/x/y', 'doinaka-v26.10.3.zip', 'https://github.com/x/y/z.zip', str_repeat('a', 64), 1, null)];
        }
    });

    try {
        $this->actingAsVerifiedAdmin($this->admin)->post('/admin/update/check')->assertRedirect()->assertSessionHas('status', '新しい版が見つかりました。');
        $this->actingAsVerifiedAdmin($this->admin)->get('/admin/update')->assertSee('v26.10.3')->assertSee('新しい版があります')->assertSee('地図のピンを直した');
    } finally {
        @unlink(base_path('VERSION'));
    }

    expect(UpdateRun::query()->count())->toBe(0)
        ->and(AuditLog::query()->where('action', AuditAction::UpdateCheck->value)->count())->toBe(1);
});

it('GitHub に繋がらなければ、分かる言葉で知らせる', function (): void {
    $this->app->bind(ReleaseSource::class, fn () => new class implements ReleaseSource
    {
        public function releases(string $repository): array
        {
            throw new RuntimeException('接続できません');
        }
    });

    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/update/check')->assertSessionHas('error');
});

it('「今すぐ更新」は管理者の名前を記録して更新する', function (): void {
    $run = new UpdateRun(['version_from' => 'v26.10.1', 'version_to' => 'v26.10.2', 'trigger' => UpdateTrigger::Manual, 'status' => UpdateStatus::Success]);
    $run->id = 7;

    $updater = Mockery::mock(AutoUpdater::class);
    $updater->shouldReceive('runManual')->once()->with('管理者さん')->andReturn($run);
    $this->app->instance(AutoUpdater::class, $updater);

    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/update/apply')->assertRedirect()->assertSessionHas('status', '更新しました。');

    $log = AuditLog::query()->where('action', AuditAction::UpdateApply->value)->firstOrFail();
    expect($log->user_id)->toBe($this->admin->id)->and($log->target_id)->toBe('7');
});

it('更新が戻ったときは、エラーとして知らせる', function (): void {
    $run = new UpdateRun(['version_from' => 'v26.10.1', 'version_to' => 'v26.10.2', 'trigger' => UpdateTrigger::Manual, 'status' => UpdateStatus::RolledBack]);
    $run->id = 8;
    $updater = Mockery::mock(AutoUpdater::class);
    $updater->shouldReceive('runManual')->andReturn($run);
    $this->app->instance(AutoUpdater::class, $updater);

    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/update/apply')->assertSessionHas('error');
});

it('自動更新・ベータ・時間帯の設定を保存し、操作ログに前後を残す', function (): void {
    $settings = app(SettingsService::class);

    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/update/settings', [
        'auto' => '1', 'window_mode' => 'fixed', 'fixed_hour' => 2,
    ])->assertRedirect()->assertSessionHas('status');

    expect($settings->bool(SettingKey::UpdateAuto))->toBeTrue()
        ->and($settings->bool(SettingKey::UpdateAcceptBeta))->toBeFalse()
        ->and($settings->string(SettingKey::UpdateWindowMode))->toBe('fixed')
        ->and($settings->int(SettingKey::UpdateFixedHour))->toBe(2);

    $log = AuditLog::query()->where('action', AuditAction::UpdateSettingsChange->value)->firstOrFail();
    expect($log->detail['before']['window_mode'])->toBe('auto')
        ->and($log->detail['after']['window_mode'])->toBe('fixed')
        ->and($log->detail['after']['fixed_hour'])->toBe(2);
});

it('設定の入力が不正なら保存しない', function (array $input): void {
    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/update/settings', $input)->assertSessionHasErrors();

    expect(AuditLog::query()->count())->toBe(0);
})->with([
    '時刻が範囲外' => [['window_mode' => 'auto', 'fixed_hour' => 24]],
    '方式が違う' => [['window_mode' => 'sometimes', 'fixed_hour' => 4]],
    '時刻がない' => [['window_mode' => 'auto']],
]);

it('Discord Webhook は暗号化して保存し、画面にも操作ログにも URL を出さない', function (): void {
    $url = 'https://discord.com/api/webhooks/123456789/AbCdEf-secret-token';

    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/update/webhook', ['webhook_url' => $url])->assertRedirect();

    expect(app(SettingsService::class)->string(SettingKey::NotifyDiscordWebhookUrl))->toBe($url);

    $this->actingAsVerifiedAdmin($this->admin)->get('/admin/update')
        ->assertOk()
        ->assertSee('設定済み')
        ->assertDontSee('secret-token')
        ->assertDontSee('discord.com/api/webhooks');

    $log = AuditLog::query()->where('action', AuditAction::NotifyWebhookChange->value)->firstOrFail();
    expect(json_encode($log->detail))->not->toContain('secret-token');
});

it('Discord 以外の URL や、http の URL は Webhook として保存できない', function (string $url): void {
    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/update/webhook', ['webhook_url' => $url])->assertSessionHasErrors('webhook_url');

    expect(app(SettingsService::class)->string(SettingKey::NotifyDiscordWebhookUrl))->toBe('');
})->with([
    'https://example.com/api/webhooks/1/x',
    'http://discord.com/api/webhooks/1/x',
    'https://discord.com.evil.example/api/webhooks/1/x',
    'javascript:alert(1)',
]);

it('空のまま「変更する」を押しても、いまの値は消えない', function (): void {
    $url = 'https://discord.com/api/webhooks/1/keep-me';
    app(SettingsService::class)->set(SettingKey::NotifyDiscordWebhookUrl, $url);

    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/update/webhook', ['webhook_url' => ''])->assertRedirect();

    expect(app(SettingsService::class)->string(SettingKey::NotifyDiscordWebhookUrl))->toBe($url);
});

it('テスト送信は Notifier を通して送り、結果を知らせる', function (): void {
    $notifier = new FakeNotifier;
    $this->app->instance(Notifier::class, $notifier);

    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/update/webhook/test')->assertSessionHas('status');

    expect($notifier->messages)->toHaveCount(1);
});
