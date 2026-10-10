<?php

declare(strict_types=1);

namespace App\Services\Install;

use App\Enums\AppMetaKey;
use App\Enums\SettingKey;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use App\Services\Update\CurrentVersion;
use Database\Seeders\InitialDataSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * インストールの実処理: .env の生成、テーブル作成、サイト情報と最初の管理者の登録。
 */
class Installer
{
    public function __construct(
        private readonly EnvFileWriter $writer,
        private readonly SettingsService $settings,
        private readonly AppMetaService $meta,
        private readonly CurrentVersion $version,
        private readonly InstallKey $key,
        private readonly InstallState $state,
        private readonly string $envPath,
        private readonly string $basePath,
    ) {}

    /**
     * .env を作る(権限 600)。APP_KEY は、インストーラーが使っていた一時の鍵をそのまま書く(セッションが途切れない)。
     *
     * @param  array{host: string, port: int, database: string, username: string, password: string}  $db
     */
    public function writeEnv(array $db, string $appUrl): void
    {
        $appKey = config()->string('app.key');
        if ($appKey === '') {
            $appKey = InstallEnvironment::temporaryKey($this->basePath);
        }

        $this->writer->write($this->envPath, [
            'APP_NAME' => 'ド田舎.net',
            'APP_ENV' => 'production',
            'APP_KEY' => $appKey,
            'APP_DEBUG' => false,
            'APP_URL' => $appUrl,
            'APP_LOCALE' => 'ja',
            'APP_FALLBACK_LOCALE' => 'ja',
            'APP_FAKER_LOCALE' => 'ja_JP',
            'APP_TIMEZONE' => 'Asia/Tokyo',
            'APP_MAINTENANCE_DRIVER' => 'file',
            'BCRYPT_ROUNDS' => 12,
            'LOG_CHANNEL' => 'stack',
            'LOG_STACK' => 'app',
            'LOG_LEVEL' => 'info',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $db['host'],
            'DB_PORT' => $db['port'],
            'DB_DATABASE' => $db['database'],
            'DB_USERNAME' => $db['username'],
            'DB_PASSWORD' => $db['password'],
            'IP_HASH_SECRET' => Str::random(64),
            'SESSION_DRIVER' => 'database',
            'SESSION_LIFETIME' => 120,
            'SESSION_ENCRYPT' => false,
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
            'FILESYSTEM_DISK' => 'local',
            'MAIL_MAILER' => 'log',
            'MAIL_FROM_ADDRESS' => 'contact@do-inaka.net',
            'MAIL_FROM_NAME' => 'ド田舎.net',
        ]);
    }

    /**
     * 入力された接続で、テーブルを作る(マイグレーション)。
     *
     * @param  array{host: string, port: int, database: string, username: string, password: string}  $db
     */
    public function migrate(array $db): void
    {
        config(['database.connections.mysql' => array_merge((array) config('database.connections.mysql'), [
            'host' => $db['host'],
            'port' => $db['port'],
            'database' => $db['database'],
            'username' => $db['username'],
            'password' => $db['password'],
        ])]);
        DB::purge('mysql');

        $code = Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
        if ($code !== 0) {
            throw new RuntimeException(__('install.migrate_failed'));
        }

        // 初期データ(地域・分類)。すでに入っていれば何もしない
        $code = Artisan::call('db:seed', ['--class' => InitialDataSeeder::class, '--force' => true, '--no-interaction' => true]);
        if ($code !== 0) {
            throw new RuntimeException(__('install.migrate_failed'));
        }
    }

    /**
     * サイト情報と最初の管理者を登録して、設置済みにする。
     *
     * @param  array{site_name: string, site_description: string, admin_name: string, admin_email: string, google_client_id: string, google_client_secret: string}  $input
     */
    public function finish(array $input, bool $ngram): User
    {
        if (User::query()->where('role', UserRole::Admin->value)->exists()) {
            throw new RuntimeException(__('install.admin_exists'));
        }

        $admin = DB::transaction(function () use ($input, $ngram): User {
            $this->settings->set(SettingKey::SiteName, $input['site_name']);
            $this->settings->set(SettingKey::SiteDescription, $input['site_description']);
            $this->settings->set(SettingKey::SearchDriver, $ngram ? 'ngram' : 'like');
            // 新しく設置したサイトは、管理者が公開前モードをオフにするまで、一般には見えない
            $this->settings->set(SettingKey::SitePrelaunch, true);
            if ($input['google_client_id'] !== '') {
                $this->settings->set(SettingKey::GoogleClientId, $input['google_client_id']);
            }
            if ($input['google_client_secret'] !== '') {
                $this->settings->set(SettingKey::GoogleClientSecret, $input['google_client_secret']);
            }

            // 最初の管理者。Google のアカウントとは、初回の Google ログイン(フェーズ2)でメールアドレスを使って結び付ける
            $admin = User::query()->create([
                'name' => $input['admin_name'],
                'email' => $input['admin_email'],
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
            ]);

            $this->meta->set(AppMetaKey::AppVersion, $this->version->get()?->__toString() ?? 'dev');
            $this->meta->markInstalled();

            return $admin;
        });

        $this->state->markReady();
        $this->key->forget();

        return $admin;
    }
}
