<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * インストーラーのセッションは、.env の設定に関係なく、暗号化したファイルに置く。
 * (途中で .env ができて DB のセッションに切り替わると、入力の途中の状態が消えるため)
 * StartSession より前に動かす必要があるので、グローバルミドルウェアの先頭に置く。
 */
final class PrepareInstallSession
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('install', 'install/*')) {
            config(['session.driver' => 'file', 'session.encrypt' => true]);
        }

        return $next($request);
    }
}
