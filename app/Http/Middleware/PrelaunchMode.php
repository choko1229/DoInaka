<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Auth\AdminTwoFactorSession;
use App\Services\Auth\TrustedDevices;
use App\Services\Setting\Prelaunch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 公開前モード。オンの間は、2段階認証まで済んだ管理者・編集者だけが、公開ページを普段どおり見られる。
 * それ以外には「準備中」(503 と Retry-After)を返す。送信系(投稿・お問い合わせ・コメントなど)も同じ門で断る。
 * 通すもの: インストーラー、ログインと Google のコールバック、管理画面、ヘルスチェック、静的ファイル、robots.txt・サイトマップ(中身は空)。
 */
final class PrelaunchMode
{
    public function __construct(
        private readonly Prelaunch $prelaunch,
        private readonly AdminTwoFactorSession $session,
        private readonly TrustedDevices $devices,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->prelaunch->isOn() || $this->isOpenPath($request) || $this->isVerifiedStaff($request)) {
            return $next($request);
        }

        $headers = ['Retry-After' => '3600', 'X-Robots-Tag' => 'noindex, nofollow'];

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => __('prelaunch.title')], 503, $headers);
        }

        return response()->view('errors.prelaunch', [], 503, $headers);
    }

    private function isOpenPath(Request $request): bool
    {
        return $request->is(
            'install', 'install/*', 'up', 'admin', 'admin/*',
            'login', 'logout', 'auth/google', 'auth/google/callback',
            'robots.txt', 'sitemap.xml', 'sitemap-*.xml',
            'build/*', 'storage/*', 'illust/*', 'favicon.ico',
        );
    }

    /** 管理画面と同じ条件(ログイン済みの管理者・編集者 + 2段階認証済み、または覚えた端末) */
    private function isVerifiedStaff(Request $request): bool
    {
        $user = $request->user();
        if ($user === null || ! $user->isStaff() || ! $user->hasTwoFactor()) {
            return false;
        }
        if ($this->session->isVerified($request)) {
            return true;
        }
        $cookie = $request->cookie(TrustedDevices::COOKIE);

        return is_string($cookie) && $this->devices->isTrusted($user, $cookie);
    }
}
