<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\SettingKey;
use App\Services\Setting\SettingsService;
use Illuminate\Http\Request;

/**
 * 「このセッションで2段階認証を通った」ことの記録。設定 admin.totp_ttl_hours(初期値12時間)で失効する。
 */
class AdminTwoFactorSession
{
    private const KEY = 'admin.totp_verified_at';

    public function __construct(private readonly SettingsService $settings) {}

    public function isVerified(Request $request): bool
    {
        $at = $request->session()->get(self::KEY);
        if (! is_int($at)) {
            return false;
        }

        return $at > now()->getTimestamp() - $this->settings->int(SettingKey::AdminTotpTtlHours) * 3600;
    }

    /** 通過を記録する。セッション ID は作り直す(固定化攻撃の対策) */
    public function markVerified(Request $request): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::KEY, now()->getTimestamp());
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::KEY);
    }
}
