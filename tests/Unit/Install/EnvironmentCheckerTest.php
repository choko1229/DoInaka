<?php

declare(strict_types=1);

use App\Enums\CheckStatus;
use App\Services\Install\EnvironmentChecker;
use Illuminate\Support\Facades\File;

function statusOf(array $results, string $labelPart): ?CheckStatus
{
    foreach ($results as $result) {
        if (str_contains($result->label, $labelPart)) {
            return $result->status;
        }
    }

    return null;
}

it('サイズの表記をバイトに直す', function (): void {
    $checker = new EnvironmentChecker(base_path());

    expect($checker->toBytes('256M'))->toBe(256 * 1048576)
        ->and($checker->toBytes('110m'))->toBe(110 * 1048576)
        ->and($checker->toBytes('1G'))->toBe(1073741824)
        ->and($checker->toBytes('512K'))->toBe(524288)
        ->and($checker->toBytes('-1'))->toBe(-1)
        ->and($checker->toBytes('128MB'))->toBe(128 * 1048576)
        ->and($checker->toBytes('abc'))->toBe(0);
});

it('PHP のバージョンと拡張機能を確かめる', function (): void {
    $results = (new EnvironmentChecker(base_path()))->run();

    expect(statusOf($results, 'PHP のバージョン'))->toBe(CheckStatus::Ok)
        ->and(statusOf($results, '必要な拡張機能'))->toBe(CheckStatus::Ok)
        ->and(statusOf($results, '画像'))->not->toBe(CheckStatus::Fail);
});

it('memory_limit が足りないと要対応になる(.htaccess が効いていないとき)', function (): void {
    $original = ini_get('memory_limit');
    // 要件(256M)より小さく、いまの使用量より大きい値にする(テストの数が増えて、全体で使う量が増えても、設定できるように)
    ini_set('memory_limit', max(64, (int) ceil(memory_get_usage(true) / 1048576) + 16).'M');

    try {
        $results = (new EnvironmentChecker(base_path()))->run();
        expect(statusOf($results, 'memory_limit'))->toBe(CheckStatus::Fail)
            ->and((new EnvironmentChecker(base_path()))->passes($results))->toBeFalse();
    } finally {
        ini_set('memory_limit', (string) $original);
    }
});

it('memory_limit が 256M 以上か無制限なら通る', function (string $value): void {
    $original = ini_get('memory_limit');
    ini_set('memory_limit', $value);

    try {
        expect(statusOf((new EnvironmentChecker(base_path()))->run(), 'memory_limit'))->toBe(CheckStatus::Ok);
    } finally {
        ini_set('memory_limit', (string) $original);
    }
})->with(['256M', '512M', '-1']);

it('書き込めないディレクトリは要対応になる', function (): void {
    $dir = sys_get_temp_dir().'/envcheck-'.bin2hex(random_bytes(4));
    mkdir($dir.'/storage', 0775, true);
    mkdir($dir.'/bootstrap/cache', 0775, true);
    chmod($dir.'/storage', 0555);

    $results = (new EnvironmentChecker($dir))->run();

    // root で動くテストでは chmod が効かないことがあるので、書き込めない環境のときだけ確かめる
    if (! is_writable($dir.'/storage')) {
        expect(statusOf($results, 'storage'))->toBe(CheckStatus::Fail);
    }

    chmod($dir.'/storage', 0775);
    expect(statusOf((new EnvironmentChecker($dir))->run(), 'bootstrap/cache'))->toBe(CheckStatus::Ok);
    File::deleteDirectory($dir);
});

it('public/.htaccess がないと注意になり、APP_DEBUG が true でも注意になる', function (): void {
    $dir = sys_get_temp_dir().'/envcheck-'.bin2hex(random_bytes(4));
    mkdir($dir.'/storage', 0775, true);
    mkdir($dir.'/bootstrap/cache', 0775, true);
    config(['app.debug' => true]);

    $results = (new EnvironmentChecker($dir))->run();

    expect(statusOf($results, '.htaccess'))->toBe(CheckStatus::Warn)
        ->and(statusOf($results, 'APP_DEBUG'))->toBe(CheckStatus::Warn);
    File::deleteDirectory($dir);
});
