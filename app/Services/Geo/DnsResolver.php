<?php

declare(strict_types=1);

namespace App\Services\Geo;

/**
 * DNS の逆引き・正引き(テストでは差し替える)。
 */
class DnsResolver
{
    public function reverse(string $ip): ?string
    {
        $host = @gethostbyaddr($ip);

        return is_string($host) && $host !== $ip ? $host : null;
    }

    /** @return list<string> */
    public function forward(string $host): array
    {
        $ips = @gethostbynamel($host);

        return is_array($ips) ? $ips : [];
    }
}
