<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SettingKey;
use App\Services\Setting\SettingsService;
use Illuminate\Console\Command;

/**
 * .env.local の開発用の値を、暗号化して settings に入れる(APP_ENV=local のときだけ)。
 *
 * 読むのは下の4つだけ。空の値は読み飛ばす。値は画面にもログにも出さない。
 */
final class ImportDevSecrets extends Command
{
    /** .env.local の変数名 => settings のキー */
    public const MAP = [
        'OPENROUTER_API_KEY' => SettingKey::AiApiKey,
        'GOOGLE_CLIENT_ID' => SettingKey::GoogleClientId,
        'GOOGLE_CLIENT_SECRET' => SettingKey::GoogleClientSecret,
        'DISCORD_WEBHOOK_URL' => SettingKey::NotifyDiscordWebhookUrl,
    ];

    protected $signature = 'dev:import-secrets {--file= : 読み込むファイル(省略すると .env.local)}';

    protected $description = '.env.local の開発用キーを暗号化して settings に入れる(APP_ENV=local のときだけ)';

    public function handle(SettingsService $settings): int
    {
        if (! app()->environment('local')) {
            $this->warn(__('dev.import_only_local'));

            return self::SUCCESS;
        }

        $path = $this->option('file');
        $path = is_string($path) && $path !== '' ? $path : base_path('.env.local');

        if (! is_file($path)) {
            $this->warn(__('dev.import_no_file'));

            return self::SUCCESS;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $imported = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\"'");

            $key = self::MAP[$name] ?? null;
            if ($key === null || $value === '') {
                continue;
            }

            $settings->set($key, $value);
            $imported++;
        }

        $this->info(__('dev.import_done', ['count' => $imported]));

        return self::SUCCESS;
    }
}
