<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Auth\RolePermissions;
use Illuminate\Support\Facades\Gate;

function userOf(UserRole $role, UserStatus $status = UserStatus::Active): User
{
    return new User(['role' => $role, 'status' => $status]);
}

it('設計書5.3の権限表どおりになっている', function (): void {
    // [権限, 閲覧者, 会員, 編集者, 管理者]
    $table = [
        [Permission::View, true, true, true, true],
        [Permission::Post, true, true, true, true],
        [Permission::Visit, true, true, true, true],
        [Permission::Comment, false, true, true, true],
        [Permission::Favorite, false, true, true, true],
        [Permission::MyPage, false, true, true, true],
        [Permission::OfficialBadge, false, false, true, true],
        [Permission::Review, false, false, true, true],
        [Permission::ManageMasters, false, false, false, true],
        [Permission::ManageSettings, false, false, false, true],
    ];

    foreach ($table as [$permission, $guest, $member, $editor, $admin]) {
        expect(RolePermissions::allows(null, $permission))->toBe($guest, "閲覧者 {$permission->value}")
            ->and(RolePermissions::allows(userOf(UserRole::Member), $permission))->toBe($member, "会員 {$permission->value}")
            ->and(RolePermissions::allows(userOf(UserRole::Editor), $permission))->toBe($editor, "編集者 {$permission->value}")
            ->and(RolePermissions::allows(userOf(UserRole::Admin), $permission))->toBe($admin, "管理者 {$permission->value}");
    }
});

it('停止中の会員は、閲覧とマイページ(退会)はできるが、投稿・コメント・反応はできない', function (): void {
    $suspended = userOf(UserRole::Member, UserStatus::Suspended);

    foreach ([Permission::View, Permission::MyPage] as $allowed) {
        expect(RolePermissions::allows($suspended, $allowed))->toBeTrue($allowed->value);
    }
    foreach ([Permission::Post, Permission::Visit, Permission::Comment, Permission::Favorite] as $denied) {
        expect(RolePermissions::allows($suspended, $denied))->toBeFalse($denied->value);
    }
});

it('停止中の管理者・編集者も、管理の権限は使えない', function (): void {
    foreach ([UserRole::Admin, UserRole::Editor] as $role) {
        $user = userOf($role, UserStatus::Suspended);
        foreach ([Permission::Review, Permission::ManageMasters, Permission::ManageSettings, Permission::OfficialBadge] as $permission) {
            expect(RolePermissions::allows($user, $permission))->toBeFalse("{$role->value} {$permission->value}");
        }
    }
});

it('すべての権限が Gate として登録されている(ルートで can:post のように使える)', function (): void {
    foreach (Permission::cases() as $permission) {
        expect(Gate::has($permission->value))->toBeTrue($permission->value);
    }
});
