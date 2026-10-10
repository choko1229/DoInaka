<?php

declare(strict_types=1);

namespace App\Support;

/**
 * ログイン後の戻り先を、サイト内のパスだけに絞る(外部サイトへ飛ばさない)。
 */
final class SafeRedirect
{
    /**
     * 先頭が「/」で、「//」「/\」「スキーム」「改行・制御文字」を含まないパスだけを通す。それ以外は $default。
     */
    public static function path(?string $candidate, string $default = '/'): string
    {
        if ($candidate === null || $candidate === '' || strlen($candidate) > 500) {
            return $default;
        }

        $unsafe = ! str_starts_with($candidate, '/')
            || str_starts_with($candidate, '//')
            || str_contains($candidate, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $candidate) === 1
            || preg_match('#^/+[a-z][a-z0-9+.-]*:#i', $candidate) === 1
            || str_contains($candidate, '://');

        return $unsafe ? $default : $candidate;
    }
}
