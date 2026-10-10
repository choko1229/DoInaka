<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\GoogleLogin;
use App\Data\GoogleIdentity;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class FakeGoogleLogin implements GoogleLogin
{
    public ?GoogleIdentity $identity = null;

    public bool $configured = true;

    public int $redirects = 0;

    public function redirect(): RedirectResponse
    {
        $this->redirects++;

        return new RedirectResponse('https://accounts.google.test/o/oauth2/auth?state=fake');
    }

    public function identity(): GoogleIdentity
    {
        return $this->identity ?? throw new RuntimeException('Google からアカウントを取得できませんでした。');
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public static function identityOf(string $sub = 'google-sub-1', string $name = 'ちょこ', string $email = 'choko@example.com', bool $verified = true): GoogleIdentity
    {
        return new GoogleIdentity($sub, $name, $email, $verified);
    }
}
