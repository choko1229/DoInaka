<?php

declare(strict_types=1);

namespace App\Support;

/**
 * 利用者が入力した文字の下ごしらえ。HTML は受け付けないので、ここでは制御文字と改行をそろえるだけ
 * (表示のときに、Blade のエスケープと nl2br(e()) で守る)。
 */
final class Text
{
    /** 1行の文字: 制御文字・改行を空白にして、前後の空白を落とす */
    public static function line(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim((string) preg_replace('/[\p{Cc}\p{Cf}\s]+/u', ' ', $value));

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    /** 複数行の文字: 改行は残し、ほかの制御文字を取り除く。3行以上の空行は詰める */
    public static function block(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = (string) preg_replace('/[^\P{Cc}\n\t]|\p{Cf}/u', '', $value);
        $value = trim((string) preg_replace("/\n{3,}/", "\n\n", $value));

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
