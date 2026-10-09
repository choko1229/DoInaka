<?php

declare(strict_types=1);

namespace App\Services\Web;

/**
 * HTML から、AI に渡す本文テキストと、同じサイト内のリンクを取り出す。画像・スクリプト・スタイルは捨てる。
 */
final class HtmlText
{
    public const MAX_CHARS = 20000;

    public static function fromHtml(string $html): string
    {
        $html = (string) preg_replace('#<(script|style|noscript|svg|nav|header|footer|form|iframe)\b[^>]*>.*?</\1>#is', ' ', $html);
        $html = (string) preg_replace('#<!--.*?-->#s', ' ', $html);
        $html = (string) preg_replace('#<(br|/p|/div|/li|/h[1-6]|/tr)\b[^>]*>#i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/[ \t\x{00A0}\x{3000}]+/u', ' ', $text);
        $text = (string) preg_replace("/\n\s*\n+/", "\n", $text);

        return mb_substr(trim($text), 0, self::MAX_CHARS);
    }

    public static function title(string $html): ?string
    {
        if (preg_match('#<title[^>]*>(.*?)</title>#is', $html, $m) !== 1) {
            return null;
        }
        $title = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $title === '' ? null : mb_substr($title, 0, 200);
    }

    /**
     * ページ内のリンクのうち、同じサイト(ホスト)の http(s) だけを、重複なしの絶対 URL で返す。
     *
     * @return list<string>
     */
    public static function sameSiteLinks(string $html, string $baseUrl): array
    {
        $parts = parse_url($baseUrl);
        if (! is_array($parts) || ! is_string($parts['scheme'] ?? null) || ! is_string($parts['host'] ?? null)) {
            return [];
        }
        $base = ['scheme' => $parts['scheme'], 'host' => $parts['host'], 'port' => isset($parts['port']) ? (int) $parts['port'] : null, 'path' => is_string($parts['path'] ?? null) ? $parts['path'] : '/'];

        preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/is', $html, $matches);
        $links = [];
        foreach ($matches[2] as $href) {
            $url = self::absolute(html_entity_decode(trim($href), ENT_QUOTES | ENT_HTML5), $base);
            $host = $url === null ? null : parse_url($url, PHP_URL_HOST);
            if ($url !== null && is_string($host) && strtolower($host) === strtolower($base['host'])) {
                $links[$url] = true;
            }
        }
        unset($links[rtrim($baseUrl, '#')]);

        return array_keys($links);
    }

    /**
     * @param  array{scheme: string, host: string, port: int|null, path: string}  $base
     */
    private static function absolute(string $href, array $base): ?string
    {
        if ($href === '' || str_starts_with($href, '#') || preg_match('#^(mailto|tel|javascript|data):#i', $href) === 1) {
            return null;
        }
        $href = (string) preg_replace('/#.*$/', '', $href);

        if (preg_match('#^https?://#i', $href) === 1) {
            return $href;
        }

        $origin = $base['scheme'].'://'.$base['host'].($base['port'] !== null ? ':'.$base['port'] : '');
        if (str_starts_with($href, '//')) {
            return $base['scheme'].':'.$href;
        }
        if (str_starts_with($href, '/')) {
            return $origin.$href;
        }

        $dir = (string) preg_replace('#/[^/]*$#', '/', $base['path']);

        return $origin.$dir.$href;
    }
}
