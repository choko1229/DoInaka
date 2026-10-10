<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\User;
use App\Services\Auth\RolePermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ログイン中の会員が、その操作をしてよいか(設計書5.1・5.3)。停止中の会員は、投稿・情報提供・コメント・反応が 403 になる
 * (ログイン・閲覧・マイページ・退会はできる)。ログインしていない人は、ここでは止めない
 * (匿名で投稿できる操作はそのまま、ログインが要る操作は各コントローラがログインへ案内する)。
 *
 * 使い方: `->middleware('permit:post')`
 */
final class EnsurePermitted
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! RolePermissions::allows($user, Permission::from($permission))) {
            abort(403);
        }

        return $next($request);
    }
}
