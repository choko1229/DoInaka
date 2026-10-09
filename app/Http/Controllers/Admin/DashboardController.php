<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminWarnings;
use Illuminate\Contracts\View\View;

/**
 * 管理画面のダッシュボード。数字や一覧はフェーズ7で足す。ここでは運営上の警告だけを出す。
 */
final class DashboardController extends Controller
{
    public function __invoke(AdminWarnings $warnings): View
    {
        return view('admin.dashboard', ['warnings' => $warnings->all()]);
    }
}
