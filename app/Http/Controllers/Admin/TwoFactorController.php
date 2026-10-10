<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AdminTwoFactorSession;
use App\Services\Auth\Totp;
use App\Services\Auth\TrustedDevices;
use App\Services\Auth\TwoFactorService;
use App\Support\SafeRedirect;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * 管理画面の2段階認証(TOTP)。②コード入力 ③初回設定 ④回復コード(AdminLoginPC / SP)。
 */
final class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly AdminTwoFactorSession $session,
        private readonly TrustedDevices $devices,
        private readonly Totp $totp,
    ) {}

    /** ②コード入力 */
    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $this->user($request);

        if (! $user->hasTwoFactor()) {
            return redirect()->route('admin.two-factor.setup');
        }
        if ($this->session->isVerified($request)) {
            return redirect($this->destination($request));
        }

        return view('admin.auth.challenge', $this->challengeData($user));
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $request->validate(['code' => ['required', 'string', 'max:20']]);

        if ($this->twoFactor->isLocked($user)) {
            return back()->withErrors(['code' => $this->lockedMessage($user)]);
        }

        if (! $this->twoFactor->verifyCode($user->refresh(), $request->string('code')->toString())) {
            return back()->withErrors(['code' => $this->failedMessage($user->refresh())]);
        }

        return $this->succeed($request, $user);
    }

    /** 回復コードの入力画面 */
    public function recovery(Request $request): View|RedirectResponse
    {
        $user = $this->user($request);

        if (! $user->hasTwoFactor()) {
            return redirect()->route('admin.two-factor.setup');
        }

        return view('admin.auth.recovery');
    }

    public function verifyRecovery(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $request->validate(['recovery_code' => ['required', 'string', 'max:30']]);

        if ($this->twoFactor->isLocked($user)) {
            return back()->withErrors(['recovery_code' => $this->lockedMessage($user)]);
        }

        if (! $this->twoFactor->verifyRecoveryCode($user->refresh(), $request->string('recovery_code')->toString())) {
            return back()->withErrors(['recovery_code' => $this->failedMessage($user->refresh())]);
        }

        return $this->succeed($request, $user);
    }

    /** ③初回設定: QR コードと手入力用のキー */
    public function setup(Request $request): View|RedirectResponse
    {
        $user = $this->user($request);

        if ($user->hasTwoFactor()) {
            return redirect()->route('admin.two-factor');
        }

        $secret = $request->session()->get('admin.totp_pending');
        if (! is_string($secret)) {
            $secret = $this->totp->generateSecret();
            $request->session()->put('admin.totp_pending', $secret);
        }

        $uri = $this->totp->uri($secret, $user->name, config()->string('app.name'));

        return view('admin.auth.setup', [
            'qr' => $this->qrSvg($uri),
            'key' => $this->totp->format($secret),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $request->validate(['code' => ['required', 'string', 'max:20']]);

        $secret = $request->session()->get('admin.totp_pending');
        if (! is_string($secret) || $user->hasTwoFactor()) {
            return redirect()->route('admin.two-factor.setup');
        }

        $codes = $this->twoFactor->enable($user->refresh(), $secret, $request->string('code')->toString(), $this->devices);
        if ($codes === null) {
            return back()->withErrors(['code' => $this->twoFactor->isLocked($user->refresh()) ? $this->lockedMessage($user) : __('auth.code_wrong_setup')]);
        }

        $request->session()->forget('admin.totp_pending');
        $this->session->markVerified($request);
        // 回復コードは、次の画面で1回だけ見せる
        $request->session()->put('admin.recovery_codes', $codes);

        return redirect()->route('admin.two-factor.codes');
    }

    /** ④回復コードを保存(1回だけ表示) */
    public function codes(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->pull('admin.recovery_codes');
        if (! is_array($codes) || $codes === []) {
            return redirect('/admin');
        }

        return view('admin.auth.codes', ['codes' => array_values(array_map(fn (mixed $code): string => is_string($code) ? $code : '', $codes))]);
    }

    /** 2段階認証を設定し直す(いまの端末が通っているときだけ)。回復コードと覚えた端末は無効になる */
    public function reset(Request $request): RedirectResponse
    {
        $user = $this->user($request);

        $this->twoFactor->reset($user, $this->devices);
        $this->session->clear($request);

        return redirect()->route('admin.two-factor.setup')->withCookie(Cookie::forget(TrustedDevices::COOKIE));
    }

    private function succeed(Request $request, User $user): RedirectResponse
    {
        $this->session->markVerified($request);
        $response = redirect($this->destination($request));

        if ($request->boolean('remember')) {
            $token = $this->devices->issue($user);
            $response->withCookie(Cookie::make(
                TrustedDevices::COOKIE, $token, $this->devices->days() * 24 * 60, '/', null, $request->isSecure(), true, false, 'lax',
            ));
        }

        return $response;
    }

    private function destination(Request $request): string
    {
        $intended = $request->session()->pull('url.intended');

        return SafeRedirect::path(is_string($intended) && str_starts_with($intended, '/admin') ? $intended : null, '/admin');
    }

    /**
     * @return array<string, mixed>
     */
    private function challengeData(User $user): array
    {
        return ['days' => $this->devices->days(), 'locked' => $this->twoFactor->isLocked($user)];
    }

    private function failedMessage(User $user): string
    {
        if ($this->twoFactor->isLocked($user)) {
            return $this->lockedMessage($user);
        }

        return __('auth.code_wrong', ['remaining' => $this->twoFactor->remainingAttempts($user), 'minutes' => TwoFactorService::LOCK_MINUTES]);
    }

    private function lockedMessage(User $user): string
    {
        $minutes = $user->totp_locked_until === null ? TwoFactorService::LOCK_MINUTES : max(1, (int) ceil(now()->diffInSeconds($user->totp_locked_until, false) / 60));

        return __('auth.locked', ['minutes' => $minutes]);
    }

    private function qrSvg(string $uri): string
    {
        $renderer = new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($uri);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }
}
