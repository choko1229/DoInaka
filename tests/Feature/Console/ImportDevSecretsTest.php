<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\Setting;
use App\Services\Setting\SettingsService;

function writeEnvLocal(string $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'envlocal');
    file_put_contents($path, $content);

    return $path;
}

it('APP_ENV が local 以外では何もせずに終わる', function (): void {
    $path = writeEnvLocal("OPENROUTER_API_KEY=sk-or-v1-should-not-be-imported\n");

    expect(app()->environment('local'))->toBeFalse();

    $this->artisan('dev:import-secrets', ['--file' => $path])
        ->assertSuccessful()
        ->expectsOutputToContain('何もしません');

    expect(Setting::query()->count())->toBe(0);
    @unlink($path);
});

it('local では4つの値だけを暗号化して settings に入れ、値は画面に出さない', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
    $path = writeEnvLocal(implode("\n", [
        '# コメントは読み飛ばす',
        'OPENROUTER_API_KEY=sk-or-v1-dev-secret-aaaa',
        'GOOGLE_CLIENT_ID="dev-client-id.apps.googleusercontent.com"',
        'GOOGLE_CLIENT_SECRET=dev-client-secret-bbbb',
        'DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/1/dev-token-cccc',
        'APP_KEY=base64:this-must-never-be-imported',
        'DB_PASSWORD=this-must-never-be-imported-either',
        '',
    ]));

    $this->artisan('dev:import-secrets', ['--file' => $path])
        ->assertSuccessful()
        ->doesntExpectOutputToContain('sk-or-v1-dev-secret-aaaa')
        ->doesntExpectOutputToContain('dev-client-secret-bbbb')
        ->doesntExpectOutputToContain('dev-token-cccc')
        ->expectsOutputToContain('4 件');

    $settings = app(SettingsService::class);
    expect($settings->string(SettingKey::AiApiKey))->toBe('sk-or-v1-dev-secret-aaaa')
        ->and($settings->string(SettingKey::GoogleClientId))->toBe('dev-client-id.apps.googleusercontent.com')
        ->and($settings->string(SettingKey::GoogleClientSecret))->toBe('dev-client-secret-bbbb')
        ->and($settings->string(SettingKey::NotifyDiscordWebhookUrl))->toBe('https://discord.com/api/webhooks/1/dev-token-cccc');

    // 保存されているのは暗号化された文字列
    foreach (['ai.api_key', 'google.client_secret', 'notify.discord_webhook_url'] as $key) {
        $row = Setting::query()->where('key', $key)->firstOrFail();
        expect($row->is_secret)->toBeTrue()->and($row->value)->not->toContain('dev-');
    }

    // 4つ以外は取り込まない
    expect(Setting::query()->count())->toBe(4);
    @unlink($path);
});

it('空の値は読み飛ばす', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
    $path = writeEnvLocal("OPENROUTER_API_KEY=\nGOOGLE_CLIENT_ID=only-this\nDISCORD_WEBHOOK_URL=\"\"\n");

    $this->artisan('dev:import-secrets', ['--file' => $path])->assertSuccessful()->expectsOutputToContain('1 件');

    expect(Setting::query()->count())->toBe(1)
        ->and(app(SettingsService::class)->string(SettingKey::GoogleClientId))->toBe('only-this');
    @unlink($path);
});

it('.env.local がなくても失敗しない', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $this->artisan('dev:import-secrets', ['--file' => sys_get_temp_dir().'/no-such-env-local'])
        ->assertSuccessful()
        ->expectsOutputToContain('何もしません');
});
