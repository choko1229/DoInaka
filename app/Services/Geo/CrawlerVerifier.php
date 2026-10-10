<?php

declare(strict_types=1);

namespace App\Services\Geo;

use Illuminate\Support\Facades\Cache;

/**
 * 検索エンジンのクローラーが本物か、逆引きと正引きで確かめる(設計書6.6)。名乗りだけでは通さない。
 */
final class CrawlerVerifier
{
    /** UA の名乗り → 逆引きで許すドメインの末尾 */
    private const CRAWLERS = [
        'Googlebot' => ['.googlebot.com', '.google.com'],
        'AdsBot-Google' => ['.googlebot.com', '.google.com'],
        'Mediapartners-Google' => ['.googlebot.com', '.google.com'],
        'bingbot' => ['.search.msn.com'],
    ];

    public function __construct(private readonly DnsResolver $dns) {}

    public function isVerified(string $ip, string $userAgent): bool
    {
        $suffixes = null;
        foreach (self::CRAWLERS as $name => $domains) {
            if (stripos($userAgent, $name) !== false) {
                $suffixes = $domains;
                break;
            }
        }
        if ($suffixes === null) {
            return false;
        }

        return Cache::remember('crawler-verified:'.$ip, now()->addDay(), fn (): bool => $this->check($ip, $suffixes));
    }

    /** @param  list<string>  $suffixes */
    private function check(string $ip, array $suffixes): bool
    {
        $host = $this->dns->reverse($ip);
        if ($host === null) {
            return false;
        }

        $host = strtolower($host);
        $ok = false;
        foreach ($suffixes as $suffix) {
            if (str_ends_with($host, $suffix)) {
                $ok = true;
            }
        }
        if (! $ok) {
            return false;
        }

        $packed = @inet_pton($ip);

        foreach ($this->dns->forward($host) as $forward) {
            if (@inet_pton($forward) === $packed) {
                return true;
            }
        }

        return false;
    }
}
