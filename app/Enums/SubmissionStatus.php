<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * 投稿の状態(設計書8章)。遷移は SubmissionStateMachine だけが行う。
 * 物理削除は行を消すことなので、状態には持たない。
 */
enum SubmissionStatus: string
{
    use HasLabel;

    case Received = 'received';
    case Processing = 'processing';
    case AiPending = 'ai_pending';
    case AiDeferred = 'ai_deferred';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case AutoRejected = 'auto_rejected';

    /** 公開テーブルに反映されない、人が見るべき(または見られる)状態 */
    public function isOpen(): bool
    {
        return in_array($this, [self::Received, self::Processing, self::AiPending, self::AiDeferred, self::InReview], true);
    }

    /** 却下ボックスに入っている状態 */
    public function isRejected(): bool
    {
        return $this === self::Rejected || $this === self::AutoRejected;
    }
}
