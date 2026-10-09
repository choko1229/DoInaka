<?php

declare(strict_types=1);

use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Middleware\EnsureNotInstalled;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Web インストーラー(設計書6.3)。設置済みなら done 以外はすべて 404
Route::prefix('install')->name('install.')->group(function (): void {
    Route::middleware(EnsureNotInstalled::class)->group(function (): void {
        Route::get('/', [InstallController::class, 'index'])->name('index');
        Route::post('/', [InstallController::class, 'verify'])->middleware('throttle:10,1')->name('verify');
        Route::get('/database', [InstallController::class, 'database'])->name('database');
        Route::post('/database', [InstallController::class, 'saveDatabase'])->middleware('throttle:10,1')->name('database.save');
        Route::get('/site', [InstallController::class, 'site'])->name('site');
        Route::post('/site', [InstallController::class, 'saveSite'])->name('site.save');
    });

    // 完了の画面は、設置した直後のセッションにだけ見せる(それ以外は 404)
    Route::get('/done', [InstallController::class, 'done'])->name('done');
});
