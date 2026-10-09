<?php

declare(strict_types=1);

namespace App\Support;

/**
 * 利用者の端末が通信する外部サービス。プライバシーポリシー(resources/legal/privacy.md「外部サービスへの送信」の表)と、
 * CSP(SecurityHeaders)の両方が、この表を元にする。サービスを足す・外すときは、この表と文面を一緒に直す(テストが突き合わせる)。
 */
final class ExternalHosts
{
    /** プライバシーポリシーの表に載せるドメイン(サービス名 => ドメイン) */
    public const LISTED = [
        'Google アナリティクス 4' => ['www.googletagmanager.com', 'www.google-analytics.com'],
        'Google AdSense' => ['pagead2.googlesyndication.com', 'googleads.g.doubleclick.net'],
        'Cloudflare Turnstile' => ['challenges.cloudflare.com'],
        'Google ログイン' => ['accounts.google.com'],
        '地理院タイル' => ['cyberjapandata.gsi.go.jp'],
    ];

    /** 上の Google AdSense・アナリティクスの動作に必要な、同じ事業者の補助的なドメイン(表には個別に載せない) */
    public const SUPPORTING = [
        'https://*.google-analytics.com',
        'https://*.analytics.google.com',
        'https://*.googletagmanager.com',
        'https://*.googlesyndication.com',
        'https://*.doubleclick.net',
        'https://ep1.adtrafficquality.google',
        'https://ep2.adtrafficquality.google',
    ];

    /** @return list<string> */
    public static function listed(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::LISTED))));
    }

    /**
     * @param  list<string>  $services  表のサービス名
     * @return list<string> https:// 付き
     */
    public static function origins(array $services): array
    {
        $hosts = [];
        foreach ($services as $service) {
            foreach (self::LISTED[$service] ?? [] as $host) {
                $hosts[] = 'https://'.$host;
            }
        }

        return $hosts;
    }
}
