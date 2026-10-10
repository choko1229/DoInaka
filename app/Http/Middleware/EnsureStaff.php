<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 管理画面の入口(2段階認証の画面など)に入れるのは、ログイン済みの管理者・編集者だけ。
 * ログインしていなければ管理画面のログインへ。会員など、それ以外には「管理画面がある」ことを見せず 404 にする。
 */
final class EnsureStaff
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            $request->session()->put('url.intended', $request->isMethod('GET') ? $request->getRequestUri() : '/admin');

            return redirect()->route('admin.login');
        }

        abort_unless($user->isStaff(), 404);

        return $next($request);
    }
}
