<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Services\Geo\DnsResolver;

/**
 * 利用者から受け取った URL を読みに行く前の安全確認(SSRF 対策。設計書13.1)。
 * http / https だけ。名前解決したアドレスがプライベート・ループバック・リンクローカル等なら拒否する。
 */
final class UrlGuard
{
    public function __construct(private readonly DnsResolver $dns) {}

    /** 読んでよい URL か。だめなら理由のキー(unsafe_scheme / unsafe_host / invalid)を返し、よければ null */
    public function problem(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return 'invalid';
        }
        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return 'unsafe_scheme';
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return 'invalid';
        }

        $host = strtolower(trim($parts['host'], '[]'));
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.internal') || str_ends_with($host, '.local')) {
            return 'unsafe_host';
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : $this->dns->forward($host);
        if ($addresses === []) {
            return 'unsafe_host';
        }

        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return 'unsafe_host';
            }
        }

        return null;
    }
}
