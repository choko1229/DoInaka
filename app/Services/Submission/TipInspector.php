<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Models\Region;

/**
 * イベントの情報提供の URL を確かめる(設計書9.7・13.1)。
 * SNS は断る。読めない・危ない URL は断る。robots.txt が禁止している URL は読まずに、管理者の確認に回す。
 * 巡回する県(crawl_enabled)の外の URL は、定期巡回には入れず、情報源の「候補」として残す。
 */
final class TipInspector
{
    /** 情報元にしない SNS・投稿サービスのドメイン(2026-10-10 決定) */
    private const SNS_HOSTS = [
        'twitter.com', 'x.com', 't.co', 'facebook.com', 'fb.com', 'fb.me', 'instagram.com', 'tiktok.com', 'threads.net',
        'threads.com', 'line.me', 'lin.ee', 'bsky.app', 'mixi.jp', 'pinterest.com',
    ];

    public function __construct(
        private readonly UrlGuard $guard,
        private readonly RobotsChecker $robots,
    ) {}

    /**
     * @return array{status: 'ok'|'sns'|'invalid'|'unsafe'|'robots_blocked', host: string|null, candidate: bool}
     */
    public function inspect(string $url, ?Region $region): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $host = is_string($host) ? strtolower($host) : null;

        if ($host === null) {
            return ['status' => 'invalid', 'host' => null, 'candidate' => false];
        }
        if ($this->isSns($host)) {
            return ['status' => 'sns', 'host' => $host, 'candidate' => false];
        }

        $problem = $this->guard->problem($url);
        if ($problem !== null) {
            return ['status' => $problem === 'invalid' ? 'invalid' : 'unsafe', 'host' => $host, 'candidate' => false];
        }

        $candidate = ! $this->inCrawlArea($region);

        if (! $this->robots->allows($url)) {
            return ['status' => 'robots_blocked', 'host' => $host, 'candidate' => $candidate];
        }

        return ['status' => 'ok', 'host' => $host, 'candidate' => $candidate];
    }

    public function isSns(string $host): bool
    {
        foreach (self::SNS_HOSTS as $sns) {
            if ($host === $sns || str_ends_with($host, '.'.$sns)) {
                return true;
            }
        }

        return false;
    }

    /** 選んだ地域が、巡回する県の中にあるか。地域がなければ、巡回の外とみなす */
    private function inCrawlArea(?Region $region): bool
    {
        if ($region === null) {
            return false;
        }

        $node = $region;
        $guard = 0;
        while ($node->parent_id !== null && $guard++ < 5) {
            $node = $node->parent()->firstOrFail();
        }

        return $node->crawl_enabled;
    }
}
