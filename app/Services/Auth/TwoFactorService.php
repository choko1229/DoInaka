<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\RecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 管理画面の2段階認証(TOTP と回復コード)。
 *
 * - 5回続けて間違えたら15分ロック(ロック中は正しいコードでも入れない)。TOTP と回復コードの失敗は合算
 * - 成功・失敗・ロックはセキュリティログに残す(コードそのものは残さない)
 * - 回復コードは8個。ハッシュで保存し、1回使うと使えなくなる
 */
class TwoFactorService
{
    public const MAX_FAILURES = 5;

    public const LOCK_MINUTES = 15;

    public const RECOVERY_CODE_COUNT = 8;

    public function __construct(private readonly Totp $totp) {}

    public function isLocked(User $user): bool
    {
        return $user->totp_locked_until !== null && $user->totp_locked_until->isFuture();
    }

    /** あと何回間違えられるか */
    public function remainingAttempts(User $user): int
    {
        return max(0, self::MAX_FAILURES - $this->failuresOf($user));
    }

    /**
     * 初回の設定: 画面に出した秘密鍵とコードが合えば、秘密鍵を暗号化して保存し、回復コードを作る。
     * 設定し直しなので、覚えていた端末はすべて無効にする。
     *
     * @return list<string>|null 回復コード(平文は、このとき1回だけ)。コードが違えば null
     */
    public function enable(User $user, string $secret, string $code, TrustedDevices $devices): ?array
    {
        if ($this->isLocked($user)) {
            return null;
        }

        $step = $this->totp->verify($secret, $code, now()->getTimestamp());
        if ($step === null) {
            $this->recordFailure($user, 'setup');

            return null;
        }

        $plain = DB::transaction(function () use ($user, $secret, $step): array {
            $user->forceFill([
                'totp_secret' => $secret,
                'totp_confirmed_at' => now(),
                'totp_last_step' => $step,
                'totp_failed_count' => 0,
                'totp_locked_until' => null,
            ])->save();

            RecoveryCode::query()->where('user_id', $user->id)->delete();

            return $this->createRecoveryCodes($user);
        });

        $devices->revokeAll($user);
        Log::channel('security')->info('2段階認証を設定しました。', ['user_id' => $user->id]);

        return $plain;
    }

    /**
     * ログイン時のコード入力。合えば true。
     */
    public function verifyCode(User $user, string $code): bool
    {
        if ($this->isLocked($user) || ! $user->hasTwoFactor() || $user->totp_secret === null) {
            return false;
        }

        $step = $this->totp->verify($user->totp_secret, $code, now()->getTimestamp(), $user->totp_last_step);
        if ($step === null) {
            $this->recordFailure($user, 'code');

            return false;
        }

        $user->forceFill(['totp_last_step' => $step, 'totp_failed_count' => 0, 'totp_locked_until' => null])->save();
        Log::channel('security')->info('2段階認証に成功しました。', ['user_id' => $user->id]);

        return true;
    }

    /**
     * 回復コードでの入力。合えば true(そのコードは使用済みになる)。
     */
    public function verifyRecoveryCode(User $user, string $input): bool
    {
        if ($this->isLocked($user)) {
            return false;
        }

        $hash = $this->hashRecoveryCode($input);
        $matched = null;

        // すべてのコードを最後まで比べる(時間一定)
        foreach (RecoveryCode::query()->where('user_id', $user->id)->whereNull('used_at')->get() as $candidate) {
            if (hash_equals($candidate->code_hash, $hash) && $matched === null) {
                $matched = $candidate;
            }
        }

        if ($matched === null) {
            $this->recordFailure($user, 'recovery');

            return false;
        }

        $matched->forceFill(['used_at' => now()])->save();
        $user->forceFill(['totp_failed_count' => 0, 'totp_locked_until' => null])->save();
        Log::channel('security')->info('回復コードでログインしました。', ['user_id' => $user->id]);

        return true;
    }

    /** 残りの回復コードの数 */
    public function remainingRecoveryCodes(User $user): int
    {
        return RecoveryCode::query()->where('user_id', $user->id)->whereNull('used_at')->count();
    }

    /** 2段階認証を外す(設定し直し)。回復コードと覚えた端末も消す */
    public function reset(User $user, TrustedDevices $devices): void
    {
        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'totp_secret' => null, 'totp_confirmed_at' => null, 'totp_last_step' => null,
                'totp_failed_count' => 0, 'totp_locked_until' => null,
            ])->save();
            RecoveryCode::query()->where('user_id', $user->id)->delete();
        });
        $devices->revokeAll($user);

        Log::channel('security')->warning('2段階認証を解除しました。', ['user_id' => $user->id]);
    }

    public function normalizeRecoveryCode(string $input): string
    {
        return strtolower(preg_replace('/[\s-]+/', '', $input) ?? '');
    }

    /**
     * @return list<string>
     */
    private function createRecoveryCodes(User $user): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            // 画面の例どおり「k7m2-p9qx」の形(紛らわしい文字を除いた8文字)
            $raw = '';
            $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
            for ($j = 0; $j < 8; $j++) {
                $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = substr($raw, 0, 4).'-'.substr($raw, 4);
            $codes[] = $code;

            RecoveryCode::query()->create(['user_id' => $user->id, 'code_hash' => $this->hashRecoveryCode($code)]);
        }

        return $codes;
    }

    private function hashRecoveryCode(string $input): string
    {
        // 回復コードは短いので、アプリの鍵を混ぜたハッシュにする(DB だけ盗まれても逆算しにくい)
        return hash_hmac('sha256', $this->normalizeRecoveryCode($input), config()->string('app.key'));
    }

    private function failuresOf(User $user): int
    {
        // ロックの期限が過ぎていたら、数え直す
        if ($user->totp_locked_until !== null && $user->totp_locked_until->isPast()) {
            return 0;
        }

        return $user->totp_failed_count;
    }

    private function recordFailure(User $user, string $kind): void
    {
        $count = $this->failuresOf($user) + 1;
        $locked = $count >= self::MAX_FAILURES;

        $user->forceFill([
            'totp_failed_count' => $locked ? 0 : $count,
            'totp_locked_until' => $locked ? now()->addMinutes(self::LOCK_MINUTES) : null,
        ])->save();

        Log::channel('security')->warning(
            $locked ? '2段階認証を続けて間違えたため、ロックしました。' : '2段階認証のコードが違います。',
            ['user_id' => $user->id, 'kind' => $kind, 'failures' => $count],
        );
    }
}
