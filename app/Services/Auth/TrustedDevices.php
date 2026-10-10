<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\SettingKey;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Str;

/**
 * 「この端末を45日間覚える」(設定 admin.remember_device_days)。
 * DB にはトークンのハッシュだけを保存し、本体は署名(暗号化)付きの Cookie に置く。
 * TOTP を設定し直したら、全端末を無効にする。
 */
class TrustedDevices
{
    public const COOKIE = 'doinaka_trusted';

    public function __construct(private readonly SettingsService $settings) {}

    public function days(): int
    {
        return $this->settings->int(SettingKey::AdminRememberDeviceDays);
    }

    /**
     * 端末を覚えて、Cookie に入れるトークンを返す。
     */
    public function issue(User $user): string
    {
        $token = Str::random(64);

        TrustedDevice::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays($this->days()),
        ]);

        return $token;
    }

    public function isTrusted(User $user, ?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $device = TrustedDevice::query()
            ->where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();

        if ($device === null) {
            return false;
        }

        $device->forceFill(['last_used_at' => now()])->save();

        return true;
    }

    public function revokeAll(User $user): void
    {
        TrustedDevice::query()->where('user_id', $user->id)->delete();
    }

    /** 期限切れを消す(定期処理から) */
    public function prune(): int
    {
        $deleted = TrustedDevice::query()->where('expires_at', '<', now())->delete();

        return is_int($deleted) ? $deleted : 0;
    }
}
