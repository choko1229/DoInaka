<?php

declare(strict_types=1);

namespace App\Services\Install;

/**
 * .env がない初回(ZIP を置いた直後)でも、インストーラーが動くようにする。
 *
 * アプリの起動前(bootstrap/app.php)に呼び、足りない環境変数を実行時だけ補う。
 * - APP_KEY: storage に書き出した一時の鍵。インストール完了後の .env にも同じ鍵を書くので、セッションが途切れない
 * - セッション・キャッシュはファイル、キューは同期(DB がまだないため)
 * すでに設定されている値(phpunit.xml や本物の環境変数)は上書きしない。
 */
final class InstallEnvironment
{
    public static function prepare(string $basePath): void
    {
        // テスト(phpunit)では何もしない。.env がない CI でも、本物の環境変数をそのまま使う
        if (is_file($basePath.'/.env') || self::get('APP_ENV') === 'testing') {
            return;
        }

        // 初回(.env がない)の目印。InstallState がトップなどからインストーラーへ案内するのに使う
        self::define('DOINAKA_FRESH_INSTALL', '1');

        $defaults = [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
        ];

        foreach ($defaults as $name => $value) {
            self::define($name, $value);
        }

        if (self::get('APP_KEY') === null) {
            self::define('APP_KEY', self::temporaryKey($basePath));
        }
    }

    /**
     * まだ設置していない(.env がないまま起動した)か。このとき、DB はまだ使えない。DB・settings・app_meta を読む前に、これで分岐する。
     * 設置の途中で .env ができたあとのリクエストは、false(DB が使える)。
     */
    public static function isFresh(): bool
    {
        return self::get('DOINAKA_FRESH_INSTALL') === '1';
    }

    public static function temporaryKey(string $basePath): string
    {
        $path = $basePath.'/storage/app/private/install-app.key';

        if (is_file($path)) {
            $key = trim((string) file_get_contents($path));
            if (str_starts_with($key, 'base64:')) {
                return $key;
            }
        }

        $key = 'base64:'.base64_encode(random_bytes(32));
        if (! is_dir(dirname($path))) {
            @mkdir(dirname($path), 0775, true);
        }
        @file_put_contents($path, $key);
        @chmod($path, 0600);

        return $key;
    }

    private static function get(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function define(string $name, string $value): void
    {
        if (self::get($name) !== null) {
            return;
        }

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        putenv($name.'='.$value);
    }
}
