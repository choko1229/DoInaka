<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SettingKey;
use App\Services\Geo\CrawlerVerifier;
use App\Services\Geo\GeoIpLookup;
use App\Services\Setting\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * 海外からのアクセス制限(設計書6.6)。日本の IP 以外は 403。
 * 例外: 本物の検索クローラー、robots.txt・サイトマップ、規約などの固定ページ、一覧が空のとき、私的アドレス。
 */
final class BlockOverseas
{
    public function __construct(
        private readonly GeoIpLookup $geo,
        private readonly CrawlerVerifier $crawlers,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('install', 'install/*', 'up')) {
            return $next($request);
        }

        try {
            if (! $this->shouldBlock($request)) {
                return $next($request);
            }
        } catch (Throwable) {
            // 判定できないときは締め出さない
            return $next($request);
        }

        return response()->view('errors.blocked', [], 403);
    }

    private function shouldBlock(Request $request): bool
    {
        if (! $this->settings->bool(SettingKey::GeoBlockOverseas)) {
            return false;
        }

        $ip = (string) $request->ip();
        if ($ip === '' || $this->geo->isPrivate($ip)) {
            return false;
        }

        $isAdmin = $request->is('admin', 'admin/*');
        if (! $isAdmin && $request->is('robots.txt', 'sitemap.xml', 'sitemap-*.xml', 'terms', 'privacy', 'about', 'contact', 'contact/*')) {
            return false;
        }

        $japan = $this->geo->isJapan($ip);
        if ($japan !== false) {
            return false;
        }

        if ($isAdmin) {
            return ! $this->settings->bool(SettingKey::GeoAllowAdminAbroad);
        }

        return ! $this->crawlers->isVerified($ip, (string) $request->userAgent());
    }
}
