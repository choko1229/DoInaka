<?php

declare(strict_types=1);

use App\Enums\ThemePreference;
use App\Http\Middleware\CountPageView;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\PrepareInstallSession;
use App\Http\Middleware\RedirectToInstaller;
use App\Services\Install\InstallEnvironment;
use App\Support\ErrorId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// .env がない初回(ZIP を置いた直後)でも、インストーラーが動くように環境を補う
InstallEnvironment::prepare(dirname(__DIR__));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 配色の選択はブラウザの JavaScript も読み書きするので、暗号化しない
        $middleware->encryptCookies(except: [ThemePreference::COOKIE]);

        // インストーラーのセッションは StartSession より前に file へ切り替える
        // 初回(.env も DB もない)は、存在しない URL でもインストーラーへ案内したいので、グローバルに置く
        $middleware->prepend([PrepareInstallSession::class, RedirectToInstaller::class]);
        $middleware->alias(['admin' => EnsureAdmin::class, 'staff' => EnsureStaff::class]);
        $middleware->web(append: [CountPageView::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // 画面に出すエラーIDと同じ値をログに残す(問い合わせから原因を追えるように)
        $exceptions->context(fn (): array => ['error_id' => app(ErrorId::class)->current()]);
    })->create();
