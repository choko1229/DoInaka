<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SettingKey;
use App\Models\AiCall;
use App\Models\AuditLog;
use App\Services\Setting\SettingsService;
use Illuminate\Console\Command;

/**
 * 保持期間を過ぎたログを消す(設計書13.2): 操作ログ(既定365日)と AI の呼び出しログ(既定90日)。毎日。
 * エラーログのファイルは、日別ファイルの保持(30日)で Laravel が消す。
 */
final class LogsPrune extends Command
{
    protected $signature = 'logs:prune';

    protected $description = '保持期間を過ぎた操作ログと AI のログを削除する';

    public function handle(SettingsService $settings): int
    {
        $audit = AuditLog::query()->where('created_at', '<', now()->subDays(max(1, $settings->int(SettingKey::LogsAuditRetentionDays))))->delete();
        $ai = AiCall::query()->where('created_at', '<', now()->subDays(max(1, $settings->int(SettingKey::LogsAiRetentionDays))))->delete();

        $this->info(__('logs.pruned', ['audit' => is_int($audit) ? $audit : 0, 'ai' => is_int($ai) ? $ai : 0]));

        return self::SUCCESS;
    }
}
