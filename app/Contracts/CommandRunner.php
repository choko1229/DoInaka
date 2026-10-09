<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * 外部コマンドの実行(更新の適用で、入れ替えたあとの版の artisan を動かす)。テストでは差し替える。
 */
interface CommandRunner
{
    /**
     * @param  list<string>  $command
     * @return array{exitCode: int, output: string}
     */
    public function run(array $command, string $workingDirectory, int $timeoutSeconds): array;
}
