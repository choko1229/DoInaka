<?php

declare(strict_types=1);

namespace App\Services\Update;

use App\Contracts\CommandRunner;
use Symfony\Component\Process\Process;

final class ProcessCommandRunner implements CommandRunner
{
    public function run(array $command, string $workingDirectory, int $timeoutSeconds): array
    {
        $process = new Process($command, $workingDirectory, null, null, $timeoutSeconds);
        $process->run();

        return ['exitCode' => $process->getExitCode() ?? 1, 'output' => $process->getOutput().$process->getErrorOutput()];
    }
}
