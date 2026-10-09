<?php

declare(strict_types=1);

namespace App\Services\Update;

use Illuminate\Contracts\Foundation\MaintenanceMode;

/**
 * 動作確認のときだけ、メンテナンス表示を無いものとして扱う(ファイルには触らない)。
 */
final class NullMaintenanceMode implements MaintenanceMode
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function activate(array $payload): void {}

    public function deactivate(): void {}

    public function active(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return [];
    }
}
