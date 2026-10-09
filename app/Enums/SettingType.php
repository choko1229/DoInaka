<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 設定値の型。保存前に検証し、読み込み時に PHP の型へ戻す。
 */
enum SettingType: string
{
    case String = 'string';
    case Int = 'int';
    case Float = 'float';
    case Bool = 'bool';
    case Json = 'json';

    /**
     * 型が合っていれば true。文字列の "5" を int として受け入れるような変換はしない。
     */
    public function accepts(mixed $value): bool
    {
        return match ($this) {
            self::String => is_string($value),
            self::Int => is_int($value),
            // 0 や 1 のような整数も小数として受け入れる(0.9 を 1 と入れた場合など)
            self::Float => is_float($value) || is_int($value),
            self::Bool => is_bool($value),
            self::Json => is_array($value),
        };
    }
}
