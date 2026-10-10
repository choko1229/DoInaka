<?php

declare(strict_types=1);

namespace App\Support;

/**
 * CSV の書き出し(ログ)。表計算ソフトで開いたときに式として実行されないよう、先頭が = + - @ のセル(と、タブ・改行から
 * 始まるセル)の頭に ' を付けて無害にする(CSV インジェクション対策。設計書13章・フェーズ7)。
 */
final class Csv
{
    /** セルの文字を無害にする */
    public static function cell(mixed $value): string
    {
        $text = match (true) {
            $value === null => '',
            is_bool($value) => $value ? '1' : '0',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };

        // 全角の ＝ ＋ − ＠ も、一部の表計算ソフトが式として扱うので、同じ扱いにする
        if ($text !== '' && preg_match('/^[=+\-@\t\r\n＝＋－＠]/u', $text) === 1) {
            return "'".$text;
        }

        return $text;
    }

    /**
     * 1行を CSV の文字列にする(UTF-8)。
     *
     * @param  list<mixed>  $cells
     */
    public static function row(array $cells): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return '';
        }
        fputcsv($stream, array_map(self::cell(...), $cells), ',', '"', '');
        rewind($stream);
        $line = (string) stream_get_contents($stream);
        fclose($stream);

        return $line;
    }
}
