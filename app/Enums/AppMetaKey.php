<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * app_meta テーブルのキー(設計書3章)。
 */
enum AppMetaKey: string
{
    case Installed = 'installed';
    case AppVersion = 'app_version';
    case SchemaVersion = 'schema_version';
    case SchedulerLastRun = 'scheduler_last_run';
    case UpdateWindowHour = 'update_window_hour';
    case CrawlWindowHour = 'crawl_window_hour';
    case AiStartedAt = 'ai_started_at';
    case LastUpdateCheck = 'last_update_check';
    case LastNotifiedVersion = 'last_notified_version';
}
