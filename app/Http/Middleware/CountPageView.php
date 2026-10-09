<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Analytics\PageViewCounter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 公開ページの閲覧を時間別に数える。管理者の閲覧・ボット・GET 以外・エラーは数えない。
 */
final class CountPageView
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|curl|wget|python-requests|headless|monitor|uptime/i';

    public function __construct(private readonly PageViewCounter $counter) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldCount($request, $response)) {
            $this->counter->record();
        }

        return $response;
    }

    private function shouldCount(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() >= 300) {
            return false;
        }
        if ($request->is('admin', 'admin/*', 'install', 'install/*', 'api/*', 'up')) {
            return false;
        }

        $user = $request->user();
        if ($user instanceof User && $user->isAdmin()) {
            return false;
        }

        return preg_match(self::BOT_PATTERN, (string) $request->userAgent()) !== 1;
    }
}
