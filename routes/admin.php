<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UpdateController;
use Illuminate\Support\Facades\Route;

/*
| 管理画面(/admin 配下。bootstrap/app.php で web ミドルウェアと prefix を付けて読み込む)。
| 画面は各フェーズで足す。
*/
Route::middleware('admin')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/update', [UpdateController::class, 'index'])->name('update');
    Route::post('/update/check', [UpdateController::class, 'check'])->name('update.check');
    Route::post('/update/apply', [UpdateController::class, 'apply'])->name('update.apply');
    Route::post('/update/settings', [UpdateController::class, 'settings'])->name('update.settings');
    Route::post('/update/webhook', [UpdateController::class, 'webhook'])->name('update.webhook');
    Route::post('/update/webhook/test', [UpdateController::class, 'webhookTest'])->name('update.webhook.test');
});
