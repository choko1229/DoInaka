<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminWarnings;
use App\Services\Admin\DashboardStats;
use App\Services\Ai\AiStatusService;
use App\Services\Ai\AiUsage;
use App\Services\Cron\WebCronStatus;
use Illuminate\Contracts\View\View;

/**
 * 管理画面のダッシュボード。数字や一覧はフェーズ7で足す。ここでは運営上の警告だけを出す。
 */
final class DashboardController extends Controller
{
    public function __invoke(AdminWarnings $warnings, AiUsage $usage, DashboardStats $stats, WebCronStatus $cron, AiStatusService $aiStatus): View
    {
        return view('admin.dashboard', [
            'warnings' => $warnings->all(),
            'counts' => $stats->counts(),
            'board' => $stats->board(),
            'recent' => $stats->recentOperations(),
            // AI の今日の使用回数(UTC の日付。OpenRouter のリセットに合わせる)と、制限エラーで止まっていること
            'cron' => ['mode' => $cron->mode(), 'lastRun' => $cron->lastRun(), 'lastWeb' => $cron->lastWebRun(), 'result' => $cron->lastResult()],
            'ai' => $aiStatus->summary(),
            'aiToday' => $usage->today(),
            'aiPausedUntil' => $usage->pausedUntil(),
        ]);
    }
}
