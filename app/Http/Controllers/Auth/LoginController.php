<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Contracts\GoogleLogin;
use App\Exceptions\SignInException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AdminTwoFactorSession;
use App\Services\Auth\GoogleSignIn;
use App\Support\SafeRedirect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

/**
 * Google ログイン(会員も管理者も)。ログインの成功・失敗はセキュリティログに残す(メールアドレスは書かない)。
 */
final class LoginController extends Controller
{
    public function __construct(
        private readonly GoogleLogin $google,
        private readonly GoogleSignIn $signIn,
    ) {}

    /** 公開側のログイン画面(LoginPC / LoginSP) */
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user() !== null) {
            return redirect(SafeRedirect::path($request->query('next') ? (string) $request->query('next') : null));
        }

        $next = SafeRedirect::path($request->query('next') ? (string) $request->query('next') : null);
        if ($next !== '/') {
            $request->session()->put('url.intended', $next);
        }

        return view('auth.login', ['configured' => $this->google->isConfigured()]);
    }

    /** Google の同意画面へ */
    public function redirect(Request $request): SymfonyRedirect
    {
        $request->session()->put('login.context', $request->boolean('admin') ? 'admin' : 'public');

        if (! $this->google->isConfigured()) {
            return redirect()->route($request->boolean('admin') ? 'admin.login' : 'login')->withErrors(['google' => __('auth.google_not_configured')]);
        }

        return $this->google->redirect();
    }

    public function callback(Request $request, AdminTwoFactorSession $twoFactor): RedirectResponse
    {
        $context = $request->session()->pull('login.context') === 'admin' ? 'admin' : 'public';
        $failure = redirect()->route($context === 'admin' ? 'admin.login' : 'login');

        try {
            $identity = $this->google->identity();
            $user = $this->signIn->resolve($identity);
        } catch (SignInException $e) {
            Log::channel('security')->warning('ログインできませんでした。', ['reason' => 'sign_in_rejected']);

            return $failure->withErrors(['google' => $e->getMessage()]);
        } catch (RuntimeException) {
            Log::channel('security')->warning('Google ログインに失敗しました。', ['reason' => 'google_failed']);

            return $failure->withErrors(['google' => __('auth.google_failed')]);
        }

        // ログインの前後でセッション ID を作り直す(固定化攻撃の対策)
        Auth::login($user);
        $request->session()->regenerate();
        $twoFactor->clear($request);

        Log::channel('security')->info('ログインしました。', ['user_id' => $user->id, 'context' => $context]);

        if ($context === 'admin') {
            return $this->afterAdminLogin($request, $user);
        }

        $intended = $request->session()->pull('url.intended');

        return redirect(SafeRedirect::path(is_string($intended) ? $intended : null));
    }

    public function logout(Request $request, AdminTwoFactorSession $twoFactor): RedirectResponse
    {
        $user = $request->user();
        if ($user instanceof User) {
            Log::channel('security')->info('ログアウトしました。', ['user_id' => $user->id]);
        }

        $twoFactor->clear($request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function afterAdminLogin(Request $request, User $user): RedirectResponse
    {
        if (! $user->isStaff()) {
            Log::channel('security')->warning('管理者ではないアカウントが管理画面にログインしようとしました。', ['user_id' => $user->id]);

            return redirect()->route('admin.login')->withErrors(['google' => __('auth.not_admin')]);
        }

        $intended = $request->session()->pull('url.intended');

        return redirect(SafeRedirect::path(is_string($intended) && str_starts_with($intended, '/admin') ? $intended : null, '/admin'));
    }
}
