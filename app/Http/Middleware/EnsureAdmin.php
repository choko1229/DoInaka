<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 管理画面に入れるのは管理者だけ。それ以外には「管理画面がある」ことを見せないよう、404 を返す。
 * (フェーズ2で、Google ログインと2段階認証の確認をここに足す)
 */
final class EnsureAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->isAdmin(), 404);

        return $next($request);
    }
}
