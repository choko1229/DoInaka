<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** お問い合わせの種類(docs/legal.md「受け付ける内容」) */
enum InquiryKind: string
{
    use HasLabel;

    case General = 'general';
    case Takedown = 'takedown';
    case Listing = 'listing';
    case Ads = 'ads';
    case Privacy = 'privacy';

    /** 返信が前提なので、メールアドレスが要る種類 */
    public function needsEmail(): bool
    {
        return in_array($this, [self::Ads, self::Privacy], true);
    }
}
