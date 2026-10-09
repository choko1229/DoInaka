<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * 2段階認証まで通った管理者として操作する(セッションに「確認済み」を入れる)。
     */
    public function actingAsVerifiedAdmin(User $user): static
    {
        return $this->actingAs($user)->withSession(['admin.totp_verified_at' => now()->getTimestamp()]);
    }

    /**
     * URL をそのまま(末尾のスラッシュも落とさずに)送る。
     * テストのクライアントの get() は、末尾のスラッシュを落としてしまうので、スラッシュを含む URL の転送のテストで使う。
     *
     * @param  array<string, string>  $server
     */
    public function rawGet(string $url, array $server = []): TestResponse
    {
        $request = Request::create($url, 'GET', [], [], [], $server);
        $response = $this->app->make(Kernel::class)->handle($request);

        return TestResponse::fromBaseResponse($response, $request);
    }

    /**
     * テスト中は CSRF の確認が省かれるので、確認を有効にする(トークンなしの操作が拒否されるかを試すとき)。
     */
    public function enforceCsrf(): static
    {
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app->make(Encrypter::class)) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });

        return $this;
    }
}
