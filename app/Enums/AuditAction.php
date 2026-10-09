<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 管理操作の記録(audit_logs.action)の種類。管理操作を足すときは、ここにも足す。
 */
enum AuditAction: string
{
    case UpdateCheck = 'update.check';
    case UpdateApply = 'update.apply';
    case UpdateSettingsChange = 'update.settings_change';
    case NotifyWebhookChange = 'notify.webhook_change';
    case NotifyWebhookTest = 'notify.webhook_test';
}
