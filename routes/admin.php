<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminBarController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventAdminController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RevisionController;
use App\Http\Controllers\Admin\TipController;
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

    // 公開ページの上端に差し込む管理者バー(設計書6.5)
    Route::get('/bar', [AdminBarController::class, 'show'])->name('bar');
    Route::post('/bar/unpublish/{type}/{id}', [AdminBarController::class, 'unpublish'])->whereNumber('id')->name('bar.unpublish');
    Route::post('/bar/regions/{region}/regenerate', [AdminBarController::class, 'regenerate'])->name('bar.regenerate');
    Route::post('/two-factor/reset', [TwoFactorController::class, 'reset'])->name('two-factor.reset');

    // 行事・開催回・スポット・記事・コメント・変更履歴(審査・コンテンツ編集は編集者も使える)
    Route::middleware('can:review')->group(function (): void {
        Route::get('/events', [EventAdminController::class, 'index'])->name('events');
        Route::get('/series/create', [EventAdminController::class, 'createSeries'])->name('series.create');
        Route::post('/series', [EventAdminController::class, 'storeSeries'])->name('series.store');
        Route::get('/series/{series}/edit', [EventAdminController::class, 'editSeries'])->name('series.edit');
        Route::put('/series/{series}', [EventAdminController::class, 'updateSeries'])->name('series.update');

        Route::get('/events/create', [EventAdminController::class, 'createEvent'])->name('events.create');
        Route::post('/events', [EventAdminController::class, 'storeEvent'])->name('events.store');
        Route::get('/events/{event}/edit', [EventAdminController::class, 'editEvent'])->name('events.edit');
        Route::put('/events/{event}', [EventAdminController::class, 'updateEvent'])->name('events.update');
        Route::delete('/events/{event}', [EventAdminController::class, 'destroyEvent'])->name('events.destroy');
        Route::post('/events/{event}/cancel', [EventAdminController::class, 'cancelEvent'])->name('events.cancel');
        Route::post('/events/{event}/copy', [EventAdminController::class, 'copyEvent'])->name('events.copy');
        Route::post('/schedules/{schedule}/cancel', [EventAdminController::class, 'cancelDay'])->name('schedules.cancel');
        Route::post('/schedules/{schedule}/restore', [EventAdminController::class, 'restoreDay'])->name('schedules.restore');

        Route::get('/contents', [ContentController::class, 'index'])->name('contents');
        Route::get('/spots/create', [ContentController::class, 'createSpot'])->name('spots.create');
        Route::post('/spots', [ContentController::class, 'storeSpot'])->name('spots.store');
        Route::get('/spots/{spot}/edit', [ContentController::class, 'editSpot'])->name('spots.edit');
        Route::put('/spots/{spot}', [ContentController::class, 'updateSpot'])->name('spots.update');
        Route::delete('/spots/{spot}', [ContentController::class, 'destroySpot'])->name('spots.destroy');
        Route::get('/articles/create', [ContentController::class, 'createArticle'])->name('articles.create');
        Route::post('/articles', [ContentController::class, 'storeArticle'])->name('articles.store');
        Route::get('/articles/{article}/edit', [ContentController::class, 'editArticle'])->name('articles.edit');
        Route::put('/articles/{article}', [ContentController::class, 'updateArticle'])->name('articles.update');
        Route::delete('/articles/{article}', [ContentController::class, 'destroyArticle'])->name('articles.destroy');
        Route::post('/comments/{comment}/moderate', [ContentController::class, 'moderateComment'])->name('comments.moderate');

        // 審査(投稿・修正依頼・情報提供・コメント。却下ボックス)。状態の変更は ReviewService だけが行う
        Route::get('/review', [ReviewController::class, 'index'])->name('review');
        Route::get('/review/rejected', [ReviewController::class, 'rejected'])->name('review.rejected');
        Route::get('/review/{submission}', [ReviewController::class, 'show'])->whereNumber('submission')->name('review.show');
        Route::post('/review/{submission}/approve', [ReviewController::class, 'approve'])->whereNumber('submission')->name('review.approve');
        Route::post('/review/{submission}/reject', [ReviewController::class, 'reject'])->whereNumber('submission')->name('review.reject');
        Route::post('/review/{submission}/restore', [ReviewController::class, 'restore'])->whereNumber('submission')->name('review.restore');
        Route::get('/corrections', [ReviewController::class, 'corrections'])->name('corrections');
        Route::get('/media/{media}/original', [ReviewController::class, 'original'])->whereNumber('media')->name('media.original');
        Route::get('/tips', [TipController::class, 'index'])->name('tips');
        Route::get('/tips/{submission}', [TipController::class, 'show'])->whereNumber('submission')->name('tips.show');
        Route::post('/tips/{submission}/mask/{media}', [TipController::class, 'mask'])->whereNumber(['submission', 'media'])->name('tips.mask');

        Route::get('/revisions/{type}/{id}', [RevisionController::class, 'index'])->whereNumber('id')->name('revisions');
        Route::post('/revisions/{revision}/rollback', [RevisionController::class, 'rollback'])->name('revisions.rollback');
    });

    // マスタ(地域・分類・タグ・NG ワード)と会員は管理者だけ
    Route::middleware('can:manage-masters')->prefix('masters')->name('masters')->group(function (): void {
        Route::get('/', [MasterController::class, 'index'])->name('');
        Route::get('/regions/{region}/edit', [MasterController::class, 'editRegion'])->name('.regions.edit');
        Route::put('/regions/{region}', [MasterController::class, 'updateRegion'])->name('.regions.update');
        Route::post('/regions/{region}/flags', [MasterController::class, 'updatePrefectureFlags'])->name('.regions.flags');
        Route::post('/categories', [MasterController::class, 'storeCategory'])->name('.categories.store');
        Route::post('/categories/{category}', [MasterController::class, 'updateCategory'])->name('.categories.update');
        Route::delete('/categories/{category}', [MasterController::class, 'destroyCategory'])->name('.categories.destroy');
        Route::post('/tags', [MasterController::class, 'storeTag'])->name('.tags.store');
        Route::delete('/tags/{tag}', [MasterController::class, 'destroyTag'])->name('.tags.destroy');
        Route::post('/ng-words', [MasterController::class, 'storeNgWord'])->name('.ng.store');
        Route::delete('/ng-words/{ngWord}', [MasterController::class, 'destroyNgWord'])->name('.ng.destroy');
    });
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
