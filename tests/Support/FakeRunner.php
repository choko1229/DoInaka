<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\CommandRunner;
use Closure;

final class FakeRunner implements CommandRunner
{
    /** @var list<list<string>> */
    public array $calls = [];

    /** @param Closure(list<string>): array{exitCode: int, output: string}|null $handler */
    public function __construct(private readonly ?Closure $handler = null) {}

    public function run(array $command, string $workingDirectory, int $timeoutSeconds): array
    {
        $this->calls[] = $command;

        return $this->handler !== null ? ($this->handler)($command) : ['exitCode' => 0, 'output' => 'ok'];
    }

    /** artisan のサブコマンド名の一覧(php -d memory_limit=… artisan <名前> …) */
    public function artisanCommands(): array
    {
        return array_map(fn (array $c): string => $c[4] ?? '', $this->calls);
    }
}
