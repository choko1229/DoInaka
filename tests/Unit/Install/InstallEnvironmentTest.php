<?php

declare(strict_types=1);

use App\Services\Install\InstallEnvironment;
use Illuminate\Support\Facades\File;

/**
 * InstallEnvironment は環境変数を書き換えるので、触った変数をテストのあとに必ず元へ戻す。
 */
function withCleanEnv(Closure $test): void
{
    $names = ['APP_ENV', 'APP_KEY', 'APP_DEBUG', 'SESSION_DRIVER', 'CACHE_STORE', 'QUEUE_CONNECTION', 'DOINAKA_FRESH_INSTALL'];
    $saved = [];
    foreach ($names as $name) {
        $saved[$name] = [$_ENV[$name] ?? null, $_SERVER[$name] ?? null, getenv($name)];
        unset($_ENV[$name], $_SERVER[$name]);
        putenv($name);
    }

    try {
        $test();
    } finally {
        foreach ($saved as $name => [$env, $server, $getenv]) {
            $env === null ? null : $_ENV[$name] = $env;
            $server === null ? null : $_SERVER[$name] = $server;
            $getenv === false ? putenv($name) : putenv($name.'='.$getenv);
        }
        foreach ($names as $name) {
            if ($saved[$name][0] === null) {
                unset($_ENV[$name]);
            }
            if ($saved[$name][1] === null) {
                unset($_SERVER[$name]);
            }
        }
    }
}

it('.env がない初回は、足りない環境変数を補い、一時の APP_KEY を storage に書く', function (): void {
    $dir = sys_get_temp_dir().'/installenv-'.bin2hex(random_bytes(4));
    mkdir($dir, 0775, true);

    withCleanEnv(function () use ($dir): void {
        InstallEnvironment::prepare($dir);

        expect($_ENV['SESSION_DRIVER'])->toBe('file')
            ->and($_ENV['CACHE_STORE'])->toBe('file')
            ->and($_ENV['QUEUE_CONNECTION'])->toBe('sync')
            ->and($_ENV['APP_DEBUG'])->toBe('false')
            ->and($_ENV['DOINAKA_FRESH_INSTALL'])->toBe('1')
            ->and($_ENV['APP_KEY'])->toStartWith('base64:')
            ->and(is_file($dir.'/storage/app/private/install-app.key'))->toBeTrue()
            ->and(file_get_contents($dir.'/storage/app/private/install-app.key'))->toBe($_ENV['APP_KEY'])
            ->and(fileperms($dir.'/storage/app/private/install-app.key') & 0777)->toBe(0600);
    });

    File::deleteDirectory($dir);
});

it('一時の APP_KEY は、次のリクエストでも同じ(セッションが途切れない)', function (): void {
    $dir = sys_get_temp_dir().'/installenv-'.bin2hex(random_bytes(4));
    mkdir($dir, 0775, true);

    $first = null;
    withCleanEnv(function () use ($dir, &$first): void {
        InstallEnvironment::prepare($dir);
        $first = $_ENV['APP_KEY'];
    });
    withCleanEnv(function () use ($dir, $first): void {
        InstallEnvironment::prepare($dir);
        expect($_ENV['APP_KEY'])->toBe($first);
    });

    File::deleteDirectory($dir);
});

it('すでにある環境変数は上書きしない', function (): void {
    $dir = sys_get_temp_dir().'/installenv-'.bin2hex(random_bytes(4));
    mkdir($dir, 0775, true);

    withCleanEnv(function () use ($dir): void {
        $_ENV['SESSION_DRIVER'] = 'database';
        $_ENV['APP_KEY'] = 'base64:from-real-env';

        InstallEnvironment::prepare($dir);

        expect($_ENV['SESSION_DRIVER'])->toBe('database')
            ->and($_ENV['APP_KEY'])->toBe('base64:from-real-env')
            ->and(is_file($dir.'/storage/app/private/install-app.key'))->toBeFalse();
    });

    File::deleteDirectory($dir);
});

it('.env がある設置済みの環境では何もしない', function (): void {
    $dir = sys_get_temp_dir().'/installenv-'.bin2hex(random_bytes(4));
    mkdir($dir, 0775, true);
    file_put_contents($dir.'/.env', 'APP_KEY=x');

    withCleanEnv(function () use ($dir): void {
        InstallEnvironment::prepare($dir);

        expect($_ENV)->not->toHaveKey('DOINAKA_FRESH_INSTALL')->not->toHaveKey('SESSION_DRIVER');
    });

    File::deleteDirectory($dir);
});

it('テスト(APP_ENV=testing)では何もしない', function (): void {
    $dir = sys_get_temp_dir().'/installenv-'.bin2hex(random_bytes(4));
    mkdir($dir, 0775, true);

    withCleanEnv(function () use ($dir): void {
        $_ENV['APP_ENV'] = 'testing';

        InstallEnvironment::prepare($dir);

        expect($_ENV)->not->toHaveKey('DOINAKA_FRESH_INSTALL');
    });

    File::deleteDirectory($dir);
});
