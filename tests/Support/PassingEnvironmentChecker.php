<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Data\CheckResult;
use App\Enums\CheckStatus;
use App\Services\Install\EnvironmentChecker;

/**
 * テストの PHP(CLI)は post_max_size などが本番の設定(.htaccess)と違うので、動作環境の確認は通ったことにする。
 * 確認の中身そのものは EnvironmentCheckerTest で確かめる。
 */
final class PassingEnvironmentChecker extends EnvironmentChecker
{
    public function run(): array
    {
        return [new CheckResult('PHP のバージョン(8.3 以上)', CheckStatus::Ok, PHP_VERSION)];
    }
}
