<?php

declare(strict_types=1);

namespace App\Services\Setting;

use App\Enums\AppMetaKey;
use App\Models\AppMeta;

/**
 * アプリとDBのバージョン、設置済みかどうかなど、設定画面では変えない値(app_meta)。
 */
class AppMetaService
{
    public function get(AppMetaKey $key): ?string
    {
        $value = AppMeta::query()->where('key', $key->value)->value('value');

        return is_string($value) ? $value : null;
    }

    public function set(AppMetaKey $key, string $value): void
    {
        AppMeta::query()->updateOrCreate(['key' => $key->value], ['value' => $value]);
    }

    public function isInstalled(): bool
    {
        return $this->get(AppMetaKey::Installed) === '1';
    }

    public function markInstalled(): void
    {
        $this->set(AppMetaKey::Installed, '1');
    }
}
