<?php

declare(strict_types=1);

namespace App\Support;

/**
 * 地域のスラッグに使えない予約語(URL の先頭の県スラッグの次に来る固定の語など。設計書9.8)。
 */
final class ReservedSlugs
{
    public const WORDS = [
        'events', 'series', 'spots', 'articles', 'map', 'post', 'report', 'login', 'logout', 'auth', 'mypage', 'users',
        'terms', 'privacy', 'policy', 'about', 'ads', 'contact', 'takedown', 'admin', 'install', 'api', 'sitemap',
        'sitemap.xml', 'robots.txt', 'up', 'build', 'storage', 'assets', 'illust', 'search', 'weekend', 'category',
        'tag', 'tags', 'new', 'edit', 'done', 'static', 'public',
    ];

    public static function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::WORDS, true);
    }
}
