<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Auth\AdminTwoFactorSession;
use App\Services\Auth\TrustedDevices;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 管理画面のミドルウェア: ログイン済み + 管理者(または編集者)+ このセッションで2段階認証を通過済み(設計書5.1)。
 *
 * - 未ログイン → 管理画面のログインへ
 * - 会員など管理者でない人 → 404(管理画面があることを見せない)
 * - 2段階認証が未設定 → 設定の画面へ。設定済みでまだ通っていない → コード入力へ
 * - 「この端末を45日間覚える」の Cookie が有効なら、コード入力を省く
 * 入ったあとの操作は、権限(Permission。ルートの can:)で決める。
 */
final class EnsureAdmin
{
    public function __construct(
        private readonly AdminTwoFactorSession $session,
        private readonly TrustedDevices $devices,
    ) {}

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

        if (! $user->hasTwoFactor()) {
            return redirect()->route('admin.two-factor.setup');
        }

        if (! $this->session->isVerified($request)) {
            $cookie = $request->cookie(TrustedDevices::COOKIE);

            if (is_string($cookie) && $this->devices->isTrusted($user, $cookie)) {
                $this->session->markVerified($request);
            } else {
                if ($request->isMethod('GET')) {
                    $request->session()->put('url.intended', $request->getRequestUri());
                }

                return redirect()->route('admin.two-factor');
            }
        }

        return $next($request);
    }
}
