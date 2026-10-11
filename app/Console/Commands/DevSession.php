<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Console\Command;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Session\Store;
use Illuminate\Support\Str;

/**
 * 開発用: 見本の会員・管理者でログインした状態のセッション Cookie を作って、JSON で出す(画面の見比べの撮影用)。
 * ローカル環境(APP_ENV=local)だけで動く。本番・テストでは何もしない。パスワードや秘密の値は出さない。
 */
final class DevSession extends Command
{
    protected $signature = 'dev:session {role=member : member または admin}';

    protected $description = '開発用のログイン済みセッション Cookie を JSON で出す(ローカル環境だけ)';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('ローカル環境だけで使えます。');

            return self::FAILURE;
        }

        $admin = $this->argument('role') === 'admin';
        $user = User::query()->firstOrCreate(
            ['email' => $admin ? 'dev-admin@example.test' : 'dev-member@example.test'],
            ['name' => $admin ? '見本の管理者' : 'さぬきの山の人', 'role' => $admin ? UserRole::Admin : UserRole::Member, 'status' => UserStatus::Active],
        );
        if ($admin && ! $user->hasTwoFactor()) {
            $user->forceFill(['totp_secret' => Str::random(32), 'totp_confirmed_at' => now()])->save();
        }

        /** @var Store $session */
        $session = app('session')->driver();
        $session->start();
        $session->put('login_web_'.sha1(SessionGuard::class), $user->id);
        if ($admin) {
            $session->put('admin.totp_verified_at', now()->getTimestamp());
        }
        $session->save();

        $name = config()->string('session.cookie');
        $encrypter = app('encrypter');
        $value = $encrypter->encrypt(CookieValuePrefix::create($name, $encrypter->getKey()).$session->getId(), false);

        $this->line((string) json_encode(['name' => $name, 'value' => $value], JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
