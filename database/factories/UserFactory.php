<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'google_sub' => (string) fake()->unique()->numerify('####################'),
            'role' => UserRole::Member,
            'status' => UserStatus::Active,
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin]);
    }

    /** 2段階認証を設定済み(秘密鍵は固定。テストで時刻から正しいコードを作れる) */
    public function twoFactor(): static
    {
        return $this->state(['totp_secret' => 'JBSWY3DPEHPK3PXP', 'totp_confirmed_at' => now()]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => UserStatus::Suspended]);
    }
}
