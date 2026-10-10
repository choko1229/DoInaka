<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * 投稿の種類(設計書3.4)。event は巡回と管理者の下書き専用で、利用者はイベントを tip(情報提供)で送る。
 */
enum SubmissionType: string
{
    use HasLabel;

    case Tip = 'tip';
    case Event = 'event';
    case Spot = 'spot';
    case Article = 'article';
    case Correction = 'correction';
    case Comment = 'comment';
    case VisitPhoto = 'visit_photo';

    /** 利用者が /post/ から送れる種類 */
    public function isPostable(): bool
    {
        return in_array($this, [self::Tip, self::Spot, self::Article], true);
    }

    /** AI で確認するため外国の事業者に送ることへの同意が要る種類(個人情報保護法28条。設計書13章) */
    public function needsOverseasConsent(): bool
    {
        return in_array($this, [self::Tip, self::Spot, self::Article, self::VisitPhoto], true);
    }
}
