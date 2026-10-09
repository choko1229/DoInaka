<?php

declare(strict_types=1);

namespace App\Services\Web;

use App\Enums\SettingKey;
use App\Services\Setting\SettingsService;
use App\Services\Submission\RobotsChecker;
use App\Services\Submission\UrlGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 公開されている Web ページを1枚読む(情報提供・巡回・URL からの下書き。設計書9.6・13.1)。
 *
 * - http / https だけ。名前解決後のアドレスがプライベート等なら読まない(SSRF)。リダイレクト先も同じ確認をする(3回まで)
 * - robots.txt(DoinakaBot と *)が禁止していたら読まない
 * - 取得は 2MB・10秒まで。同じサイトへは min_interval_seconds(既定10秒)以上あける
 * - ETag・Last-Modified を渡すと、変わっていなければ本文を読まない
 */
final class UrlFetcher
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    public const MAX_REDIRECTS = 3;

    public function __construct(
        private readonly UrlGuard $guard,
        private readonly RobotsChecker $robots,
        private readonly SettingsService $settings,
    ) {}

    public function fetch(string $url, ?string $etag = null, ?string $lastModified = null): FetchResult
    {
        $current = $url;
        $response = null;

        // リダイレクトは自分でたどる(3回まで)。1回ごとに、行き先が読んでよい場所か・robots.txt が許すかを確かめる
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            if ($this->guard->problem($current) !== null) {
                return new FetchResult(FetchResult::FAILED, reason: $hop === 0 ? 'unsafe' : 'unsafe_redirect');
            }
            if (! $this->robots->allows($current)) {
                return new FetchResult(FetchResult::BLOCKED, reason: 'robots');
            }

            $this->waitTurn($current);

            $headers = ['Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9'];
            if ($hop === 0 && $etag !== null) {
                $headers['If-None-Match'] = $etag;
            }
            if ($hop === 0 && $lastModified !== null) {
                $headers['If-Modified-Since'] = $lastModified;
            }

            try {
                $response = Http::withUserAgent(RobotsChecker::USER_AGENT)->withHeaders($headers)->timeout(10)
                    ->withOptions(['allow_redirects' => false] + $this->pin($current))
                    ->get($current);
            } catch (Throwable) {
                return new FetchResult(FetchResult::FAILED, reason: 'connection');
            }

            $location = $response->header('Location');
            if (in_array($response->status(), [301, 302, 303, 307, 308], true) && $location !== '') {
                $next = $this->absolute($location, $current);
                if ($next === null) {
                    return new FetchResult(FetchResult::FAILED, reason: 'bad_redirect');
                }
                $current = $next;
                $response = null;

                continue;
            }

            break;
        }

        if ($response === null) {
            return new FetchResult(FetchResult::FAILED, reason: 'too_many_redirects');
        }

        if ($response->status() === 304) {
            return new FetchResult(FetchResult::NOT_MODIFIED, etag: $etag, lastModified: $lastModified);
        }
        if (! $response->successful()) {
            return new FetchResult(FetchResult::FAILED, reason: 'http_'.$response->status());
        }

        $type = strtolower((string) $response->header('Content-Type'));
        if ($type !== '' && ! str_contains($type, 'html') && ! str_contains($type, 'xml') && ! str_contains($type, 'text/plain')) {
            return new FetchResult(FetchResult::FAILED, reason: 'content_type');
        }

        $etagHeader = $response->header('ETag');
        $modified = $response->header('Last-Modified');

        return new FetchResult(
            FetchResult::OK,
            substr($response->body(), 0, self::MAX_BYTES),
            $etagHeader === '' ? null : $etagHeader,
            $modified === '' ? null : $modified,
        );
    }

    /**
     * 確かめたアドレスに、接続先を固定する(名前解決のあいだに別のアドレスへ差し替えられる攻撃への備え)。
     *
     * @return array<string, mixed>
     */
    private function pin(string $url): array
    {
        $ip = $this->guard->pinnedAddress($url);
        if ($ip === null || ! defined('CURLOPT_RESOLVE')) {
            return [];
        }

        $parts = parse_url($url);
        $host = is_array($parts) && is_string($parts['host'] ?? null) ? strtolower($parts['host']) : '';
        $port = is_array($parts) && is_int($parts['port'] ?? null) ? $parts['port'] : (($parts['scheme'] ?? 'https') === 'http' ? 80 : 443);

        return $host === '' ? [] : ['curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"]]];
    }

    private function absolute(string $location, string $base): ?string
    {
        if (preg_match('#^https?://#i', $location) === 1) {
            return $location;
        }
        $parts = parse_url($base);
        if (! is_array($parts) || ! is_string($parts['scheme'] ?? null) || ! is_string($parts['host'] ?? null)) {
            return null;
        }
        $origin = $parts['scheme'].'://'.$parts['host'].(is_int($parts['port'] ?? null) ? ':'.$parts['port'] : '');

        if (str_starts_with($location, '/')) {
            return $origin.$location;
        }

        return $origin.(string) preg_replace('#/[^/]*$#', '/', is_string($parts['path'] ?? null) ? $parts['path'] : '/').$location;
    }

    /** 同じサイトへのアクセスの間隔をあける */
    private function waitTurn(string $url): void
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $interval = max(0, $this->settings->int(SettingKey::CrawlMinIntervalSeconds));
        if ($host === '' || $interval === 0) {
            return;
        }

        $key = 'fetch-last:'.$host;
        $last = Cache::get($key);
        if (is_float($last) || is_int($last)) {
            $wait = $interval - (microtime(true) - $last);
            if ($wait > 0) {
                usleep((int) ($wait * 1_000_000));
            }
        }
        Cache::put($key, microtime(true), now()->addMinutes(5));
    }
}
