<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\UserChangeRefused;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * 会員の停止・解除・権限の変更(設計書5.3・6.2)。すべて操作ログに残す。
 * 利用できる管理者が0人にならないよう、最後の管理者の権限を外す・停止することは断る。
 */
final class UserManager
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** 利用中の管理者が、この人だけか */
    public function isLastAdmin(User $user): bool
    {
        if ($user->role !== UserRole::Admin || $user->status !== UserStatus::Active) {
            return false;
        }

        return User::query()->where('role', UserRole::Admin)->where('status', UserStatus::Active)->whereKeyNot($user->id)->doesntExist();
    }

    /** @throws UserChangeRefused */
    public function suspend(User $target, User $actor): void
    {
        if ($this->isLastAdmin($target)) {
            throw new UserChangeRefused(__('users.refuse_last_admin'));
        }
        if ($target->status === UserStatus::Suspended) {
            return;
        }

        $target->forceFill(['status' => UserStatus::Suspended])->save();
        $this->audit->record(AuditAction::UserSuspend, $actor, 'user', $target->id);
    }

    public function restore(User $target, User $actor): void
    {
        if ($target->status === UserStatus::Active) {
            return;
        }

        $target->forceFill(['status' => UserStatus::Active])->save();
        $this->audit->record(AuditAction::UserRestore, $actor, 'user', $target->id);
    }

    /**
     * 権限(ロール)を変える。変えたあとの管理者が0人になるなら断る(自分自身を外すときも同じ)。
     *
     * @throws UserChangeRefused
     */
    public function changeRole(User $target, UserRole $role, User $actor): void
    {
        if ($target->role === $role) {
            return;
        }

        DB::transaction(function () use ($target, $role, $actor): void {
            // 同時に2人が互いを外して、管理者が0人にならないよう、管理者の行をロックして数える
            $admins = User::query()->where('role', UserRole::Admin)->where('status', UserStatus::Active)->lockForUpdate()->pluck('id')->all();
            if ($target->role === UserRole::Admin && $role !== UserRole::Admin && $admins === [$target->id]) {
                throw new UserChangeRefused(__('users.refuse_last_admin'));
            }

            $before = $target->role;
            $target->forceFill(['role' => $role])->save();
            $this->audit->record(AuditAction::UserRoleChange, $actor, 'user', $target->id, ['before' => $before->value, 'after' => $role->value]);
        });
    }
}
