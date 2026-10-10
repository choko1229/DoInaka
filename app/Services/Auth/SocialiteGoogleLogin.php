<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\GoogleLogin;
use App\Data\GoogleIdentity;
use App\Enums\SettingKey;
use App\Services\Setting\SettingsService;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

/**
 * Laravel Socialite の Google ログイン。クライアント ID・シークレットは settings(暗号化)から読む。
 * 戻り先は APP_URL(メインのド田舎.net)の1つだけ。do-inaka.net から始めても、先にメインへ転送されている(設計書16章)。
 */
final class SocialiteGoogleLogin implements GoogleLogin
{
    public function __construct(private readonly SettingsService $settings) {}

    public function isConfigured(): bool
    {
        return $this->settings->string(SettingKey::GoogleClientId) !== ''
            && $this->settings->string(SettingKey::GoogleClientSecret) !== '';
    }

    public function redirect(): RedirectResponse
    {
        $redirect = $this->provider()->scopes(['openid', 'profile', 'email'])->redirect();

        return $redirect;
    }

    public function identity(): GoogleIdentity
    {
        try {
            $user = $this->provider()->user();
        } catch (Throwable $e) {
            // state の不一致・ユーザーが拒否した・Google 側の失敗。詳細は呼び出し側でログに残す
            throw new RuntimeException('Google からアカウントを取得できませんでした。', 0, $e);
        }

        /** @var array<string, mixed> $raw */
        $raw = $user instanceof SocialiteUser ? $user->getRaw() : [];
        $id = (string) $user->getId();
        $email = $user->getEmail();

        if ($id === '' || ! is_string($email) || $email === '') {
            throw new RuntimeException('Google のアカウント情報が足りません。');
        }

        $verified = ($raw['email_verified'] ?? $raw['verified_email'] ?? false) === true;
        $name = $user->getName();
        $fallbackName = strstr($email, '@', true);

        return new GoogleIdentity(
            $id,
            is_string($name) && $name !== '' ? $name : ($fallbackName !== false && $fallbackName !== '' ? $fallbackName : $email),
            $email,
            $verified,
        );
    }

    private function provider(): GoogleProvider
    {
        /** @var GoogleProvider $provider */
        $provider = Socialite::buildProvider(GoogleProvider::class, [
            'client_id' => $this->settings->string(SettingKey::GoogleClientId),
            'client_secret' => $this->settings->string(SettingKey::GoogleClientSecret),
            'redirect' => rtrim(config()->string('app.url'), '/').'/auth/google/callback',
        ]);

        return $provider;
    }
}
