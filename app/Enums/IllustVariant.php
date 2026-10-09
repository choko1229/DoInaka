<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 元の PNG(1536×1024)から切り出す3つの形。
 *
 * wide: 上下を切った 1536×512(PC の FV)
 * card: 左右を切った 1200×900(スマホの FV・写真がないカード)
 * card-sm: card を縮めた 600×450(一覧のカード)
 */
enum IllustVariant: string
{
    case Wide = 'wide';
    case Card = 'card';
    case CardSm = 'card-sm';

    public function width(): int
    {
        return match ($this) {
            self::Wide => 1536,
            self::Card => 1200,
            self::CardSm => 600,
        };
    }

    public function height(): int
    {
        return match ($this) {
            self::Wide => 512,
            self::Card => 900,
            self::CardSm => 450,
        };
    }
}
