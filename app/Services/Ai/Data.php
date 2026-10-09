<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * AI に渡す「判定対象のデータ」を、区切りで包む(設計書9.4。プロンプトインジェクション対策)。
 * 中身にある区切りの文字列は無害な字に替えて、データの中から抜け出せないようにする。
 */
final class Data
{
    public static function wrap(string $label, string $text): string
    {
        $text = str_replace(['<<<DATA', 'DATA>>>'], ['<<<data', 'data>>>'], $text);

        return $label."\n<<<DATA\n".$text."\nDATA>>>";
    }
}
