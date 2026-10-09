<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Admin\UpdateController;
use Illuminate\Support\Facades\Route;

/*
| 管理画面(/admin 配下。bootstrap/app.php で web ミドルウェアと prefix を付けて読み込む)。
| 画面は各フェーズで足す。入ったあとの操作は、権限(can:)で絞る。
*/

// ログイン(Google)。誰でも開ける
Route::get('/login', AdminLoginController::class)->name('login');

// 2段階認証。ログイン済みの管理者・編集者だけ(まだ2段階認証を通っていなくてよい)
Route::middleware('staff')->prefix('two-factor')->name('two-factor')->group(function (): void {
    Route::get('/', [TwoFactorController::class, 'challenge'])->name('');
    Route::post('/', [TwoFactorController::class, 'verify'])->middleware('throttle:30,1')->name('.verify');
    Route::get('/recovery', [TwoFactorController::class, 'recovery'])->name('.recovery');
    Route::post('/recovery', [TwoFactorController::class, 'verifyRecovery'])->middleware('throttle:30,1')->name('.recovery.verify');
    Route::get('/setup', [TwoFactorController::class, 'setup'])->name('.setup');
    Route::post('/setup', [TwoFactorController::class, 'enable'])->middleware('throttle:30,1')->name('.enable');
    Route::get('/codes', [TwoFactorController::class, 'codes'])->name('.codes');
});

// ここから先は、管理者(または編集者)で、このセッションで2段階認証を通った人だけ
Route::middleware('admin')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/two-factor/reset', [TwoFactorController::class, 'reset'])->name('two-factor.reset');

    // 設定・AI・広告・更新適用は管理者だけ(設計書5.3)
    Route::middleware('can:manage-settings')->group(function (): void {
        Route::get('/update', [UpdateController::class, 'index'])->name('update');
        Route::post('/update/check', [UpdateController::class, 'check'])->name('update.check');
        Route::post('/update/apply', [UpdateController::class, 'apply'])->name('update.apply');
        Route::post('/update/settings', [UpdateController::class, 'settings'])->name('update.settings');
        Route::post('/update/webhook', [UpdateController::class, 'webhook'])->name('update.webhook');
        Route::post('/update/webhook/test', [UpdateController::class, 'webhookTest'])->name('update.webhook.test');
    });
});
