<?php

declare(strict_types=1);

namespace App\Services\Security;

/**
 * IP アドレスのハッシュ(IP_HASH_SECRET を鍵にした HMAC)。IP そのものは保存・ログに出さない。
 */
final class IpHasher
{
    public function __construct(private readonly string $secret) {}

    public function hash(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, $this->secret);
    }
}
