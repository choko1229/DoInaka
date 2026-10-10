<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Setting\Prelaunch;
use App\Support\ExternalHosts;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * すべての応答にセキュリティヘッダーを付ける(設計書13章)。
 * CSP: スクリプトは自分のドメインと、使う外部サービス(ExternalHosts)だけ。インラインのスクリプト・イベント属性は使わない。
 * スタイルは style 属性を使っているので 'unsafe-inline' を許す(スクリプトは許さない)。
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = [
            'Content-Security-Policy' => $this->csp(),
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(self), camera=(), microphone=(), payment=(), usb=(), interest-cohort=()',
        ];
        // 公開前モードの間は、すべての応答を検索エンジンに載せない
        if (app(Prelaunch::class)->isOn()) {
            $headers['X-Robots-Tag'] = 'noindex, nofollow';
        }
        // HSTS は HTTPS のときだけ(開発の http に付けると、ブラウザが http を使えなくなる)
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }

    public function csp(): string
    {
        $analytics = ExternalHosts::origins(['Google アナリティクス 4']);
        $ads = ExternalHosts::origins(['Google AdSense']);
        $turnstile = ExternalHosts::origins(['Cloudflare Turnstile']);
        $login = ExternalHosts::origins(['Google ログイン']);
        $tiles = ExternalHosts::origins(['地理院タイル']);
        $supporting = ExternalHosts::SUPPORTING;
        $dev = $this->devServer();

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", ...$analytics, ...$ads, ...$turnstile, ...$supporting, ...$dev],
            'style-src' => ["'self'", "'unsafe-inline'", ...$dev],
            'img-src' => ["'self'", 'data:', ...$tiles, ...$analytics, ...$ads, ...$supporting],
            'font-src' => ["'self'", 'data:', ...$dev],
            'connect-src' => ["'self'", ...$analytics, ...$ads, ...$supporting, ...$dev],
            'frame-src' => [...$turnstile, ...$ads, ...$supporting],
            'form-action' => ["'self'", ...$login],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        $parts = [];
        foreach ($directives as $name => $values) {
            $parts[] = $name.' '.implode(' ', array_values(array_unique($values)));
        }

        return implode('; ', $parts);
    }

    /**
     * 開発(Vite の開発サーバーを動かしているとき)だけ、その待ち受け先を許す。
     *
     * @return list<string>
     */
    private function devServer(): array
    {
        if (! app()->environment('local')) {
            return [];
        }
        $hot = public_path('hot');
        if (! is_file($hot)) {
            return [];
        }
        $url = trim((string) file_get_contents($hot));
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['host'])) {
            return [];
        }
        $authority = $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        return ['http://'.$authority, 'ws://'.$authority, 'http://localhost:*', 'ws://localhost:*'];
    }
}
