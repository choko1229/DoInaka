<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Data\GoogleIdentity;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\SignInException;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Google のアカウントから、会員を探す・結び付ける・作る。
 *
 * 1. google_sub が同じ会員 → その会員(2回目以降のログイン)
 * 2. google_sub が空で、メールアドレスが同じ会員 → 結び付ける(インストーラーで作った最初の管理者)
 * 3. どちらでもなければ会員を新しく作る(受け取るのは名前とメールだけ)
 * メールアドレスが Google で確認済みでなければ、どれもしない。
 * 停止中の会員も、ログインはできる(投稿などが権限で止まる)。
 */
class GoogleSignIn
{
    /**
     * @throws SignInException
     */
    public function resolve(GoogleIdentity $identity): User
    {
        if (! $identity->emailVerified) {
            throw new SignInException(__('auth.email_not_verified'));
        }

        $bySub = User::query()->where('google_sub', $identity->sub)->first();
        if ($bySub !== null) {
            return $bySub;
        }

        $pending = User::query()->where('email', $identity->email)->whereNull('google_sub')->first();
        if ($pending !== null) {
            $pending->forceFill(['google_sub' => $identity->sub])->save();

            return $pending;
        }

        // 同じメールアドレスの会員が、別の Google アカウントで登録されている
        if (User::query()->where('email', $identity->email)->exists()) {
            throw new SignInException(__('auth.email_taken'));
        }

        try {
            return User::query()->create([
                'name' => mb_substr($identity->name, 0, 100),
                'email' => $identity->email,
                'google_sub' => $identity->sub,
                'role' => UserRole::Member,
                'status' => UserStatus::Active,
            ]);
        } catch (UniqueConstraintViolationException) {
            // 同時に2回ログインされたときは、先に作られた会員を使う
            return User::query()->where('google_sub', $identity->sub)->firstOrFail();
        }
    }
}
