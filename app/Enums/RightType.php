<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** 削除依頼で、侵害されているとする権利 */
enum RightType: string
{
    use HasLabel;

    case Copyright = 'copyright';
    case Portrait = 'portrait';
    case Privacy = 'privacy';
    case Defamation = 'defamation';
    case Other = 'other';

    /** 住所・電話番号・名前や人の写り込みは、急ぎで確認する */
    public function isUrgent(): bool
    {
        return in_array($this, [self::Portrait, self::Privacy], true);
    }

    /** 写真を「タップで表示」できるか。プライバシーと肖像権は、確認中は見せない */
    public function allowsReveal(): bool
    {
        return ! $this->isUrgent();
    }
}
