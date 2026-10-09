<?php

declare(strict_types=1);

use App\Contracts\Notifier;
use App\Enums\AppMetaKey;
use App\Services\Setting\AppMetaService;
use App\Services\Update\CronHealth;
use App\Services\Update\UpdateLock;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\Support\FakeNotifier;
use Tests\Support\InMemoryMaintenance;

it('動作確認: DB・ビルド済みアセット・トップページが通れば成功し、メンテナンス表示中でも確かめられる', function (): void {
    $manifest = public_path('build/manifest.json');
    $created = false;
    if (! is_file($manifest)) {
        @mkdir(dirname($manifest), 0775, true);
        file_put_contents($manifest, '{}');
        $created = true;
    }

    try {
        $this->artisan('update:health-check')->assertSuccessful()->expectsOutputToContain('成功');
    } finally {
        if ($created) {
            unlink($manifest);
        }
    }
});

it('動作確認: ビルド済みアセットがないと失敗する', function (): void {
    $manifest = public_path('build/manifest.json');
    $backup = null;
    if (is_file($manifest)) {
        $backup = (string) file_get_contents($manifest);
        unlink($manifest);
    }

    try {
        $this->artisan('update:health-check')->assertFailed()->expectsOutputToContain('ビルド済み');
    } finally {
        if ($backup !== null) {
            file_put_contents($manifest, $backup);
        }
    }
});

it('動作確認: ページが 500 を返すと失敗する', function (): void {
    $manifest = public_path('build/manifest.json');
    $created = false;
    if (! is_file($manifest)) {
        @mkdir(dirname($manifest), 0775, true);
        file_put_contents($manifest, '{}');
        $created = true;
    }
    config(['update.health_paths' => ['/__health_boom']]);
    Route::middleware('web')->get('/__health_boom', fn () => abort(500));

    try {
        $this->artisan('update:health-check')->assertFailed()->expectsOutputToContain('/__health_boom');
    } finally {
        if ($created) {
            unlink($manifest);
        }
    }
});

it('更新の途中で残ったメンテナンス表示は、ロックされておらず時間が過ぎていれば解除する', function (): void {
    $maintenance = new InMemoryMaintenance;
    $maintenance->activate(['reason' => 'update', 'since' => time() - 31 * 60]);
    $notifier = new FakeNotifier;
    $this->app->instance(MaintenanceMode::class, $maintenance);
    $this->app->instance(Notifier::class, $notifier);
    $this->app->instance(UpdateLock::class, new UpdateLock(sys_get_temp_dir().'/recover-'.bin2hex(random_bytes(4)).'.lock'));

    $this->artisan('update:recover')->assertSuccessful();

    expect($maintenance->on)->toBeFalse()->and($notifier->messages)->toHaveCount(1);
});

it('更新が動いている間(ロック中)や、始まって間もないときは解除しない', function (): void {
    $lockPath = sys_get_temp_dir().'/recover-'.bin2hex(random_bytes(4)).'.lock';
    $holder = new UpdateLock($lockPath);
    expect($holder->acquire())->toBeTrue();

    $maintenance = new InMemoryMaintenance;
    $maintenance->activate(['reason' => 'update', 'since' => time() - 3600]);
    $this->app->instance(MaintenanceMode::class, $maintenance);
    $this->app->instance(UpdateLock::class, new UpdateLock($lockPath));

    $this->artisan('update:recover')->assertSuccessful();
    expect($maintenance->on)->toBeTrue('ロック中は解除しない');
    $holder->release();

    $fresh = new InMemoryMaintenance;
    $fresh->activate(['reason' => 'update', 'since' => time() - 60]);
    $this->app->instance(MaintenanceMode::class, $fresh);
    $this->artisan('update:recover')->assertSuccessful();
    expect($fresh->on)->toBeTrue('始まって間もないときは解除しない');
});

it('人が手で down にしたメンテナンスには触らない', function (): void {
    $maintenance = new InMemoryMaintenance;
    $maintenance->activate(['retry' => 60]);
    $this->app->instance(MaintenanceMode::class, $maintenance);

    $this->artisan('update:recover')->assertSuccessful();

    expect($maintenance->on)->toBeTrue();
});

it('メンテナンスしていなければ何もしない', function (): void {
    $this->app->instance(MaintenanceMode::class, new InMemoryMaintenance);
    $notifier = new FakeNotifier;
    $this->app->instance(Notifier::class, $notifier);

    $this->artisan('update:recover')->assertSuccessful();

    expect($notifier->messages)->toBe([]);
});

it('スケジューラが動くたびに最終実行時刻を書き、5分以上なければ止まったとみなす', function (): void {
    $cron = app(CronHealth::class);
    expect($cron->isStale())->toBeTrue();

    $cron->beat();
    expect($cron->isStale())->toBeFalse()->and($cron->lastRun())->not->toBeNull();

    Carbon::setTestNow(now()->addMinutes(4)->addSeconds(59));
    expect($cron->isStale())->toBeFalse();

    Carbon::setTestNow(now()->addSeconds(2));
    expect($cron->isStale())->toBeTrue();
    Carbon::setTestNow();
});

it('定期処理(cron の1行から動く)が登録されている', function (): void {
    $names = collect(app(Schedule::class)->events())->map(fn ($e) => $e->description)->filter()->all();

    expect($names)->toContain('cron-heartbeat', 'queue-work', 'pageviews-flush', 'update-recalculate-window', 'update-run', 'update-recover');
});

it('heartbeat の定期処理を動かすと app_meta に最終実行時刻が入る', function (): void {
    $event = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'cron-heartbeat');
    expect($event)->not->toBeNull();

    $event->run($this->app);

    expect(app(AppMetaService::class)->get(AppMetaKey::SchedulerLastRun))->not->toBeNull();
});
