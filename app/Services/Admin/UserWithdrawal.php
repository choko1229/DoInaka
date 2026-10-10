<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\AuditAction;
use App\Exceptions\UserChangeRefused;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * 会員の退会(マイページ。設計書5.1)。個人を特定できる情報(名前・メール・Google の ID)を消し、
 * 公開された投稿は残して投稿者を匿名にする。お気に入り・行った!の記録は消す。最後の管理者は退会できない。
 */
final class UserWithdrawal
{
    public function __construct(private readonly UserManager $users, private readonly AuditLogger $audit) {}

    /** @throws UserChangeRefused */
    public function withdraw(User $user): void
    {
        if ($this->users->isLastAdmin($user)) {
            throw new UserChangeRefused(__('users.refuse_last_admin'));
        }

        DB::transaction(function () use ($user): void {
            foreach (['events', 'spots', 'articles'] as $table) {
                DB::table($table)->where('author_user_id', $user->id)->update(['author_user_id' => null, 'is_anonymous' => true]);
            }
            DB::table('submissions')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('media')->where('uploader_user_id', $user->id)->update(['uploader_user_id' => null]);
            DB::table('comments')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('visits')->where('user_id', $user->id)->delete();
            DB::table('favorites')->where('user_id', $user->id)->delete();

            $this->audit->record(AuditAction::UserWithdraw, null, 'user', $user->id);
            // 残った行(信頼した端末・回復コード・セッションなど)は、会員の行を消すと一緒に消える
            $user->delete();
        });
    }
}
