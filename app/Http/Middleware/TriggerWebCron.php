<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Cron\WebCronTrigger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * 応答を返したあとで、アクセスをきっかけに予約処理を動かす(WebCronTrigger)。利用者の表示は待たせない。
 */
final class TriggerWebCron
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (config('app.web_cron') !== true || $request->is('install', 'install/*', 'up', 'cron/*')) {
            return;
        }

        try {
            app(WebCronTrigger::class)->fire();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
