<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\DB;

/*
 * 設置済み(.env がある)のときは、今までどおり settings を DB から読み、正規 URL への 301 も動く。
 * 設置前の分岐(InstallEnvironment::isFresh)が、設置済みの動きを変えていないことの確認。
 */
it('設置済みでは、settings を DB から読む(保存した値が返る)', function (): void {
    $settings = app(SettingsService::class);
    $settings->set(SettingKey::SiteShareHost, 'share.example.test');
    $settings->flush();

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    expect($settings->string(SettingKey::SiteShareHost))->toBe('share.example.test')->and($queries)->toBeGreaterThan(0);
});

it('設置済みでは、app_meta も DB から読む', function (): void {
    $meta = app(AppMetaService::class);
    $meta->markInstalled();

    expect($meta->isInstalled())->toBeTrue();
});

it('設置済みでは、正規 URL への 301 が動く(共有用ドメインの設定を使う)', function (): void {
    config(['app.canonical_redirects' => true]);
    app(SettingsService::class)->set(SettingKey::SiteShareHost, 'share.example.test');

    $this->get('http://share.example.test/terms/')->assertStatus(301)->assertRedirect('http://localhost/terms/');
});
