<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

/**
 * ロールと権限の対応表(設計書5.3)と、判定。
 */
final class RolePermissions
{
    /** ログインしていない閲覧者 */
    private const GUEST = [Permission::View, Permission::Post, Permission::Visit];

    private const MEMBER = [
        Permission::View, Permission::Post, Permission::Visit, Permission::Comment, Permission::Favorite, Permission::MyPage,
    ];

    /**
     * @return list<Permission>
     */
    public static function for(?UserRole $role): array
    {
        return match ($role) {
            null => self::GUEST,
            UserRole::Member => self::MEMBER,
            UserRole::Editor => [...self::MEMBER, Permission::OfficialBadge, Permission::Review],
            UserRole::Admin => Permission::cases(),
        };
    }

    public static function allows(?User $user, Permission $permission): bool
    {
        if ($user === null) {
            return in_array($permission, self::for(null), true);
        }

        if ($user->status === UserStatus::Suspended && $permission->blockedWhenSuspended()) {
            return false;
        }

        return in_array($permission, self::for($user->role), true);
    }
}
