<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Services\Auth\SocialiteGoogleLogin;
use App\Services\Setting\SettingsService;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

function socialiteUser(array $raw, ?string $email = 'choko@example.com', ?string $name = 'ちょこ', string $id = '1234567890'): SocialiteUser
{
    $user = new SocialiteUser;
    $user->setRaw($raw)->map(['id' => $id, 'name' => $name, 'email' => $email]);

    return $user;
}

it('クライアント ID とシークレットが settings(暗号化)にあれば、設定済みと判断する', function (): void {
    $settings = app(SettingsService::class);
    $login = new SocialiteGoogleLogin($settings);
    expect($login->isConfigured())->toBeFalse();

    $settings->set(SettingKey::GoogleClientId, 'client-id');
    expect($login->isConfigured())->toBeFalse();

    $settings->set(SettingKey::GoogleClientSecret, 'client-secret');
    expect($login->isConfigured())->toBeTrue();
});

it('戻り先は APP_URL の /auth/google/callback の1つだけ(do-inaka.net から始めても分かれない)', function (): void {
    $settings = app(SettingsService::class);
    $settings->set(SettingKey::GoogleClientId, 'client-id');
    $settings->set(SettingKey::GoogleClientSecret, 'client-secret');
    config(['app.url' => 'https://xn--gdkt37rmci.net']);

    $captured = null;
    Socialite::shouldReceive('buildProvider')->once()->andReturnUsing(function (string $class, array $config) use (&$captured): GoogleProvider {
        $captured = $config;

        return Mockery::mock(GoogleProvider::class);
    });

    // identity() が provider を作るところまで(user() は失敗させる)
    try {
        (new SocialiteGoogleLogin($settings))->identity();
    } catch (RuntimeException) {
    }

    expect($captured['redirect'])->toBe('https://xn--gdkt37rmci.net/auth/google/callback')
        ->and($captured['client_id'])->toBe('client-id')
        ->and($captured['client_secret'])->toBe('client-secret');
});

it('Google のアカウント(sub・名前・メール・確認済みか)を受け取る', function (array $raw, bool $verified): void {
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn(socialiteUser($raw));
    Socialite::shouldReceive('buildProvider')->andReturn($provider);

    $identity = (new SocialiteGoogleLogin(app(SettingsService::class)))->identity();

    expect($identity->sub)->toBe('1234567890')
        ->and($identity->name)->toBe('ちょこ')
        ->and($identity->email)->toBe('choko@example.com')
        ->and($identity->emailVerified)->toBe($verified);
})->with([
    '確認済み' => [['email_verified' => true], true],
    '未確認' => [['email_verified' => false], false],
    '項目なし(未確認として扱う)' => [[], false],
    '文字列の true は確認済みとしない' => [['email_verified' => 'true'], false],
]);

it('名前がなければメールアドレスの前半を使う。メールがなければ失敗にする', function (): void {
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn(socialiteUser(['email_verified' => true], name: null), socialiteUser(['email_verified' => true], email: null));
    Socialite::shouldReceive('buildProvider')->andReturn($provider);
    $login = new SocialiteGoogleLogin(app(SettingsService::class));

    expect($login->identity()->name)->toBe('choko');
    expect(fn () => $login->identity())->toThrow(RuntimeException::class);
});

it('Google が失敗を返したら(state の不一致・拒否など)、中身を出さずに RuntimeException にする', function (): void {
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andThrow(new Exception('Client error: invalid_grant secret-detail'));
    Socialite::shouldReceive('buildProvider')->andReturn($provider);

    expect(fn () => (new SocialiteGoogleLogin(app(SettingsService::class)))->identity())
        ->toThrow(RuntimeException::class, 'Google からアカウントを取得できませんでした。');
});
