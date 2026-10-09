<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\AdminBarController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DraftController;
use App\Http\Controllers\Admin\EventAdminController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\RegionPageController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RevisionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SourceController;
use App\Http\Controllers\Admin\SuggestController;
use App\Http\Controllers\Admin\TipController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Admin\UpdateController;
use App\Http\Controllers\Admin\UserAdminController;
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
        Route::post('/corrections/{submission}/confirm', [ReviewController::class, 'confirmCorrection'])->whereNumber('submission')->name('corrections.confirm');
        Route::post('/corrections/{submission}/rollback', [ReviewController::class, 'rollbackCorrection'])->whereNumber('submission')->name('corrections.rollback');
        Route::get('/drafts', [DraftController::class, 'index'])->name('drafts');
        Route::post('/drafts', [DraftController::class, 'read'])->middleware('throttle:20,1')->name('drafts.read');
        Route::post('/drafts/save', [DraftController::class, 'save'])->name('drafts.save');
        Route::post('/suggest', SuggestController::class)->middleware('throttle:20,1')->name('suggest');
        Route::get('/region-pages', [RegionPageController::class, 'index'])->name('region-pages');
        Route::post('/region-pages/regenerate', [RegionPageController::class, 'regenerate'])->name('region-pages.regenerate');
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

    // 会員の管理(管理者だけ。設計書5.3)
    Route::middleware('can:manage-masters')->prefix('users')->name('users')->group(function (): void {
        Route::get('/', [UserAdminController::class, 'index'])->name('');
        Route::get('/{user}', [UserAdminController::class, 'show'])->whereNumber('user')->name('.show');
        Route::post('/{user}/suspend', [UserAdminController::class, 'suspend'])->whereNumber('user')->name('.suspend');
        Route::post('/{user}/restore', [UserAdminController::class, 'restore'])->whereNumber('user')->name('.restore');
        Route::post('/{user}/role', [UserAdminController::class, 'role'])->whereNumber('user')->name('.role');
    });
    // 情報源の巡回は管理者だけ(設計書6.2)
    Route::middleware('can:manage-masters')->prefix('sources')->name('sources')->group(function (): void {
        Route::get('/', [SourceController::class, 'index'])->name('');
        Route::get('/create', [SourceController::class, 'create'])->name('.create');
        Route::post('/', [SourceController::class, 'store'])->name('.store');
        Route::get('/{source}/edit', [SourceController::class, 'edit'])->whereNumber('source')->name('.edit');
        Route::put('/{source}', [SourceController::class, 'update'])->whereNumber('source')->name('.update');
        Route::delete('/{source}', [SourceController::class, 'destroy'])->whereNumber('source')->name('.destroy');
        Route::post('/{source}/run', [SourceController::class, 'run'])->whereNumber('source')->name('.run');
        Route::post('/{source}/pause', [SourceController::class, 'pause'])->whereNumber('source')->name('.pause');
        Route::post('/{source}/resume', [SourceController::class, 'resume'])->whereNumber('source')->name('.resume');
        Route::post('/{source}/trust', [SourceController::class, 'trust'])->whereNumber('source')->name('.trust');
        Route::post('/candidates/{candidate}/ignore', [SourceController::class, 'ignoreCandidate'])->whereNumber('candidate')->name('.candidates.ignore');
    });
    // 設定・AI・広告・更新適用は管理者だけ(設計書5.3)
    Route::middleware('can:manage-settings')->group(function (): void {
        Route::get('/settings/{tab?}', [SettingsController::class, 'show'])->where('tab', '[a-z]+')->name('settings');
        Route::post('/settings/{tab}', [SettingsController::class, 'update'])->where('tab', '[a-z]+')->name('settings.update');
        Route::get('/logs/{tab?}', [LogController::class, 'index'])->where('tab', 'operations|reviews|ai|errors')->name('logs');
        Route::get('/logs/{tab}/csv', [LogController::class, 'csv'])->where('tab', 'operations|reviews|ai|errors')->middleware('throttle:10,1')->name('logs.csv');
        Route::get('/ads', [AdController::class, 'index'])->name('ads');
        Route::post('/ads/adsense', [AdController::class, 'saveAdsense'])->name('ads.adsense');
        Route::get('/ads/create', [AdController::class, 'create'])->name('ads.create');
        Route::post('/ads', [AdController::class, 'store'])->name('ads.store');
        Route::get('/ads/{slot}/edit', [AdController::class, 'edit'])->whereNumber('slot')->name('ads.edit');
        Route::put('/ads/{slot}', [AdController::class, 'update'])->whereNumber('slot')->name('ads.update');
        Route::delete('/ads/{slot}', [AdController::class, 'destroy'])->whereNumber('slot')->name('ads.destroy');
        Route::get('/update', [UpdateController::class, 'index'])->name('update');
        Route::post('/update/check', [UpdateController::class, 'check'])->name('update.check');
        Route::post('/update/apply', [UpdateController::class, 'apply'])->name('update.apply');
        Route::post('/update/settings', [UpdateController::class, 'settings'])->name('update.settings');
        Route::post('/update/webhook', [UpdateController::class, 'webhook'])->name('update.webhook');
        Route::post('/update/webhook/test', [UpdateController::class, 'webhookTest'])->name('update.webhook.test');
    });
});
