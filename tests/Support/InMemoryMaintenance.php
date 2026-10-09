<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Contracts\Foundation\MaintenanceMode;

final class InMemoryMaintenance implements MaintenanceMode
{
    public bool $on = false;

    /** @var array<string, mixed> */
    public array $payload = [];

    public int $activations = 0;

    public function activate(array $payload): void
    {
        $this->on = true;
        $this->payload = $payload;
        $this->activations++;
    }

    public function deactivate(): void
    {
        $this->on = false;
    }

    public function active(): bool
    {
        return $this->on;
    }

    public function data(): array
    {
        return $this->payload;
    }
}
