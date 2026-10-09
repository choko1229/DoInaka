<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\EventListController;
use App\Http\Controllers\Api\ReactionController;
use App\Http\Controllers\Api\RegionController as RegionApiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\EventController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\MapController;
use App\Http\Controllers\Public\MediaFileController;
use App\Http\Controllers\Public\RegionController;
use App\Http\Controllers\Public\SeoController;
use App\Http\Controllers\Public\SeriesController;
use App\Http\Controllers\Public\SpotController;
use App\Http\Controllers\Public\SubmissionController;
use App\Http\Middleware\EnsureNotInstalled;
use App\Support\ReservedSlugs;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// ログイン(会員も管理者も Google。管理画面のログインは routes/admin.php)
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::get('/auth/google', [LoginController::class, 'redirect'])->middleware('throttle:20,1')->name('auth.google');
Route::get('/auth/google/callback', [LoginController::class, 'callback'])->middleware('throttle:20,1')->name('auth.google.callback');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

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

// SEO(設計書15章)
Route::get('/sitemap.xml', [SeoController::class, 'index'])->name('sitemap');
Route::get('/sitemap-{kind}.xml', [SeoController::class, 'show'])->where('kind', '[a-z]+')->name('sitemap.kind');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// API v1(絞り込みの部分更新、お気に入り、行った!)
Route::prefix('api/v1')->name('api.')->group(function (): void {
    Route::get('/events', EventListController::class)->middleware('throttle:60,1')->name('events');
    Route::post('/favorites/{type}/{id}', [ReactionController::class, 'favorite'])->whereNumber('id')->middleware('throttle:60,1')->name('favorites');
    Route::post('/visits/{type}/{id}', [ReactionController::class, 'visit'])->whereNumber('id')->middleware('throttle:60,1')->name('visits');
    Route::post('/{type}/{id}/comments', [CommentController::class, 'store'])->whereIn('type', ['event', 'spot', 'article'])->whereNumber('id')->middleware('throttle:20,1')->name('comments');
    Route::get('/regions', [RegionApiController::class, 'index'])->middleware('throttle:120,1')->name('regions');
    Route::get('/regions/nearest', [RegionApiController::class, 'nearest'])->middleware('throttle:120,1')->name('regions.nearest');
});

// 公開用の画像(public/storage のリンクがない環境の代わり。リンクがあれば Web サーバーが直接返す)
Route::get('/storage/{path}', [MediaFileController::class, 'show'])->where('path', 'media/.+')->name('media.file');

// 投稿・修正依頼・「行った!」の写真(設計書6.1)。受付は Turnstile・件数制限・同意つき
Route::prefix('post')->name('post.')->group(function (): void {
    Route::get('/', [SubmissionController::class, 'index'])->name('index');
    Route::get('/done', [SubmissionController::class, 'done'])->name('done');
    Route::get('/photo/{type}/{id}', [SubmissionController::class, 'photo'])->whereNumber('id')->name('photo');
    Route::post('/photo/{type}/{id}', [SubmissionController::class, 'storePhoto'])->whereNumber('id')->middleware('throttle:20,1')->name('photo.store');
    Route::get('/{type}', [SubmissionController::class, 'create'])->where('type', 'tip|spot|article')->name('create');
    Route::post('/{type}', [SubmissionController::class, 'store'])->where('type', 'tip|spot|article')->middleware('throttle:20,1')->name('store');
});
Route::get('/report/{type}/{id}', [SubmissionController::class, 'report'])->whereNumber('id')->name('report');
Route::post('/report/{type}/{id}', [SubmissionController::class, 'storeReport'])->whereNumber('id')->middleware('throttle:20,1')->name('report.store');

// 公開ページ(設計書6.1)。URL の先頭は県のスラッグ。予約語は県のスラッグにならない。有効でない県は 404
$pref = '(?!(?:'.implode('|', array_filter(ReservedSlugs::WORDS, fn (string $w): bool => preg_match('/^[a-z0-9-]+$/', $w) === 1)).')(?![a-z0-9-]))[a-z0-9-]+';
$slug = '[a-z0-9-]+';

Route::prefix('{pref}')->where(['pref' => $pref, 'segment' => '[0-9]+(?:-[a-z0-9-]*)?', 'city' => $slug, 'old' => $slug, 'category' => $slug])->group(function (): void {
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/weekend', [EventController::class, 'index'])->defaults('mode', 'weekend')->name('events.weekend');
    Route::get('/events/category/{category}', [EventController::class, 'index'])->name('events.category');
    Route::get('/events/{segment}', [EventController::class, 'show'])->name('events.show');
    Route::get('/series/{segment}', [SeriesController::class, 'show'])->name('series.show');
    Route::get('/spots', [SpotController::class, 'index'])->name('spots.index');
    Route::get('/spots/{segment}', [SpotController::class, 'show'])->name('spots.show');
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/{segment}', [ArticleController::class, 'show'])->name('articles.show');
    Route::get('/map', [MapController::class, 'index'])->name('map');

    // 地域ページ(県・市区町村・旧町村)。上のどれにも合わないものだけ
    Route::get('/', [RegionController::class, 'show'])->name('region.pref');
    Route::get('/{city}', [RegionController::class, 'show'])->name('region.city');
    Route::get('/{city}/{old}', [RegionController::class, 'show'])->name('region.old');
});
