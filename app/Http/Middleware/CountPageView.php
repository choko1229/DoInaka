<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Analytics\PageViewCounter;
use App\Services\Analytics\TrafficFilter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 公開ページの閲覧を時間別に数える。管理者の閲覧・ボット・GET 以外・エラーは数えない。
 */
final class CountPageView
{
    public function __construct(private readonly PageViewCounter $counter, private readonly TrafficFilter $filter) {}

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

        // 管理者・編集者とボットは数えない(個別ページの閲覧数と同じ規則)
        return $this->filter->countsViewer($request);
    }
}
