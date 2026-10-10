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
    case ContentCreate = 'content.create';
    case ContentUpdate = 'content.update';
    case ContentDelete = 'content.delete';
    case ContentRollback = 'content.rollback';
    case EventCancel = 'event.cancel';
    case EventCopy = 'event.copy';
    case MasterCreate = 'master.create';
    case MasterUpdate = 'master.update';
    case MasterDelete = 'master.delete';
    case CommentModerate = 'comment.moderate';
    case SubmissionReview = 'submission.review';
    case SubmissionPublish = 'submission.publish';
    case UserSuspend = 'user.suspend';
    case UserRestore = 'user.restore';
    case UserRoleChange = 'user.role_change';
    case UserViewEmail = 'user.view_email';
    case UserWithdraw = 'user.withdraw';
    case SettingsChange = 'settings.change';
    case AdChange = 'ad.change';
    case LogExport = 'log.export';
    case InquiryHandle = 'inquiry.handle';
    case InquiryReply = 'inquiry.reply';
    case TakedownRemove = 'takedown.remove';
    case TakedownKeep = 'takedown.keep';
}
