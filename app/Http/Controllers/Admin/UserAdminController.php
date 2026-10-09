<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\UserChangeRefused;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\UserManager;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 会員の管理(AdminUsers。設計書6.2): 一覧、停止と解除、権限の変更。管理者だけ。
 * メールアドレスは詳細の画面でだけ出し、出したことを操作ログに残す(設計書3.5)。
 */
final class UserAdminController extends Controller
{
    public function __construct(private readonly UserManager $users, private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $q = trim($request->string('q')->toString());
        $role = UserRole::tryFrom($request->string('role')->toString());
        $status = UserStatus::tryFrom($request->string('status')->toString());

        $query = User::query()->orderByDesc('id');
        if ($q !== '') {
            // 一覧では、メールアドレスを検索にも使わない(詳細の画面でだけ扱う)
            $query->where('name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%');
        }
        if ($role !== null) {
            $query->where('role', $role);
        }
        if ($status !== null) {
            $query->where('status', $status);
        }

        return view('admin.users.index', ['users' => $query->paginate(30)->withQueryString(), 'q' => $q, 'role' => $role, 'status' => $status]);
    }

    public function show(Request $request, User $user): View
    {
        // メールアドレスを画面に出す = 操作ログに残す
        $this->audit->record(AuditAction::UserViewEmail, $this->actor($request), 'user', $user->id);

        return view('admin.users.show', ['member' => $user, 'isLastAdmin' => $this->users->isLastAdmin($user)]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        try {
            $this->users->suspend($user, $this->actor($request));
        } catch (UserChangeRefused $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('users.suspended_ok'));
    }

    public function restore(Request $request, User $user): RedirectResponse
    {
        $this->users->restore($user, $this->actor($request));

        return back()->with('status', __('users.restored_ok'));
    }

    public function role(Request $request, User $user): RedirectResponse
    {
        $request->validate(['role' => ['required', Rule::enum(UserRole::class)]]);

        try {
            $this->users->changeRole($user, UserRole::from($request->string('role')->toString()), $this->actor($request));
        } catch (UserChangeRefused $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('users.role_ok'));
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }
}
