<?php

declare(strict_types=1);

namespace App\Services\Url;

use App\Enums\SettingKey;
use App\Services\Setting\SettingsService;
use Illuminate\Http\Request;

/**
 * 正規 URL を決める(設計書15.1)。
 *
 * - 正規 URL は https://xn--gdkt37rmci.net/(APP_URL)。共有用の do-inaka.net、www 付き、http は、同じパス・同じクエリの正規 URL へ **1回の** 301 で転送する
 * - 公開ページの URL は末尾スラッシュありに統一し、スラッシュなし・大文字を含むものは 301 でそろえる
 * - 知らないホスト名(開発の localhost など)では転送しない
 * - 管理画面・API・インストーラー・認証・ファイル(sitemap.xml など)の URL は、スラッシュや大文字に触らない(ホストとスキームの転送だけ)
 */
class UrlCanonicalizer
{
    /** 公開ページではないパスの先頭(スラッシュの正規化をしない) */
    private const NON_PAGE_PREFIXES = ['admin', 'api', 'install', 'auth', 'up', 'build', 'storage', 'logout', 'illust'];

    public function __construct(private readonly SettingsService $settings) {}

    public function mainHost(): string
    {
        return (string) (parse_url(config()->string('app.url'), PHP_URL_HOST) ?: 'localhost');
    }

    /** ホスト名(開発では :8080 のようなポート付き。本番は 80/443 なので付かない) */
    public function mainAuthority(): string
    {
        $port = parse_url(config()->string('app.url'), PHP_URL_PORT);

        return $this->mainHost().(is_int($port) ? ':'.$port : '');
    }

    /**
     * 正規 URL のスキーム。APP_URL のスキームだが、**https で来たリクエストには、必ず https を返す**。
     * APP_URL が http のまま(設置を http で行った)でも、https のアクセスを http へ転送しない。
     * http へ転送すると、HSTS(https だけを使う指示)を覚えたブラウザが、すぐ https へ戻して、転送が終わらなくなる(ERR_TOO_MANY_REDIRECTS)。
     */
    public function mainScheme(): string
    {
        if (app()->bound('request') && request()->isSecure()) {
            return 'https';
        }

        return (string) (parse_url(config()->string('app.url'), PHP_URL_SCHEME) ?: 'http');
    }

    public function shareHost(): string
    {
        return strtolower($this->settings->string(SettingKey::SiteShareHost));
    }

    /** 共有ボタン・QR コードに使う URL(do-inaka.net) */
    public function shareUrl(string $path): string
    {
        return $this->mainScheme().'://'.$this->shareHost().'/'.ltrim($path, '/');
    }

    /** 正規の URL(メインのホスト)。$path は先頭が / のパス */
    public function canonicalUrl(string $path, ?string $query = null): string
    {
        return $this->mainScheme().'://'.$this->mainAuthority().$path.($query === null || $query === '' ? '' : '?'.$query);
    }

    /**
     * 正規と違えば、転送先の URL を返す。同じ(または知らないホスト)なら null。
     */
    public function redirectTarget(Request $request): ?string
    {
        if (! config('app.canonical_redirects') || (! $request->isMethod('GET') && ! $request->isMethod('HEAD'))) {
            return null;
        }

        $host = strtolower($request->getHost());
        $main = strtolower($this->mainHost());
        $share = $this->shareHost();

        $known = [$main, 'www.'.$main, $share, 'www.'.$share];
        if (! in_array($host, $known, true)) {
            return null;
        }

        $path = $request->getPathInfo();
        $targetPath = $this->isPage($path) ? $this->normalizePath($path) : $path;

        $schemeOk = $request->getScheme() === $this->mainScheme();
        if ($host === $main && $schemeOk && $targetPath === $path) {
            return null;
        }

        return $this->canonicalUrl($targetPath, $request->getQueryString());
    }

    /** 公開ページ(スラッシュと大文字をそろえる対象)か */
    public function isPage(string $path): bool
    {
        if ($path === '/' || $path === '') {
            return true;
        }

        $segments = explode('/', trim($path, '/'));
        if (in_array(strtolower($segments[0]), self::NON_PAGE_PREFIXES, true)) {
            return false;
        }

        // ファイル(sitemap.xml・robots.txt など)
        return ! str_contains((string) end($segments), '.');
    }

    private function normalizePath(string $path): string
    {
        $path = strtolower($path);
        $path = (string) preg_replace('#/{2,}#', '/', $path);

        return str_ends_with($path, '/') ? $path : $path.'/';
    }
}
