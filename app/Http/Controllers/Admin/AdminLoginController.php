<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\GoogleLogin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理画面のログイン(AdminLoginPC / SP の ①ログイン)。Google ログイン自体は公開側と同じ。
 */
final class AdminLoginController extends Controller
{
    public function __invoke(Request $request, GoogleLogin $google): View|RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User && $user->isStaff()) {
            return redirect('/admin');
        }

        return view('admin.auth.login', ['configured' => $google->isConfigured()]);
    }
}
