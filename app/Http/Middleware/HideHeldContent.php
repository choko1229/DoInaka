<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Scopes\HeldContentScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 公開側のリクエストの間だけ、削除依頼の確認中のページを一覧などから外す(管理画面では外さない)。
 */
final class HideHeldContent
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*')) {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        }

        HeldContentScope::enable();
        try {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        } finally {
            HeldContentScope::disable();
        }
    }
}
