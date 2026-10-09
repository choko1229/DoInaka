<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * 2段階認証まで通った管理者として操作する(セッションに「確認済み」を入れる)。
     */
    public function actingAsVerifiedAdmin(User $user): static
    {
        return $this->actingAs($user)->withSession(['admin.totp_verified_at' => now()->getTimestamp()]);
    }
}
