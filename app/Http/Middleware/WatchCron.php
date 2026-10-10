<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Update\CronWatcher;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * 応答を返したあとで、1分に1回だけ cron の停止を確かめる(利用者を待たせない。失敗しても何も起きない)。
 */
final class WatchCron
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (config('app.cron_watch') !== true || $request->is('up', 'install', 'install/*') || ! Cache::add('cron-watch:throttle', 1, 60)) {
            return;
        }

        try {
            app(CronWatcher::class)->check();
        } catch (Throwable) {
            // DB が使えないときなどは、確かめない
        }
    }
}
