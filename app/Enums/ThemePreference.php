<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 利用者が選ぶ配色の設定(フッターの切り替え)。Cookie に保存する。
 */
enum ThemePreference: string
{
    case Auto = 'auto';
    case Day = 'day';
    case Night = 'night';

    public const COOKIE = 'doinaka_theme';

    public static function fromCookie(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Auto;
    }
}
