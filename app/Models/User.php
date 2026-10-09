<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $google_sub
 * @property string|null $bio
 * @property int $approved_count
 * @property UserRole $role
 * @property UserStatus $status
 * @property string|null $totp_secret
 * @property Carbon|null $totp_confirmed_at
 * @property int|null $totp_last_step
 * @property int $totp_failed_count
 * @property Carbon|null $totp_locked_until
 */
#[Fillable(['name', 'email', 'google_sub', 'role', 'status', 'totp_secret', 'totp_confirmed_at', 'totp_last_step', 'totp_failed_count', 'totp_locked_until'])]
#[Hidden(['email', 'google_sub', 'remember_token', 'totp_secret', 'totp_last_step'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'status' => UserStatus::class,
            // TOTP の秘密鍵は暗号化して保存する
            'totp_secret' => 'encrypted',
            'totp_confirmed_at' => 'datetime',
            'totp_locked_until' => 'datetime',
        ];
    }

    /** 管理画面に入れるロール(管理者・編集者)。入ったあとの操作は権限(Permission)で決める */
    public function isStaff(): bool
    {
        return $this->role === UserRole::Admin || $this->role === UserRole::Editor;
    }

    public function hasTwoFactor(): bool
    {
        return $this->totp_secret !== null && $this->totp_confirmed_at !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }
}
