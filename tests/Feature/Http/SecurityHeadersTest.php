<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Exceptions\InvalidSettingValueException;
use App\Models\User;
use App\Services\Setting\SettingsService;
use App\Support\ExternalHosts;
use Illuminate\Testing\TestResponse;

function assertSecurityHeaders(TestResponse $response): void
{
    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'")->toContain("frame-ancestors 'none'")->toContain("object-src 'none'")
        ->and($response->headers->get('Permissions-Policy'))->toContain('geolocation=(self)');
}

it('公開ページ・固定ページ・フォーム・管理画面・404・robots.txt のすべてにセキュリティヘッダーが付く', function (): void {
    foreach (['/', '/terms/', '/privacy/', '/contact/', '/post/', '/admin/login', '/robots.txt', '/sitemap.xml', '/no-such-page-xyz/zzz/', '/api/v1/regions'] as $path) {
        assertSecurityHeaders($this->get($path));
    }
});

it('ログイン後の管理画面・JSON の応答・リダイレクトにも付く', function (): void {
    assertSecurityHeaders($this->post('/logout'));
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());
    assertSecurityHeaders($this->get('/admin/'));
});

it('HSTS は HTTPS の応答だけに付く', function (): void {
    $this->get('/terms/')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/terms/')->assertOk()->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('CSP のスクリプトは unsafe-inline を許さない。スタイルだけ許す', function (): void {
    $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');
    $script = collect(explode('; ', $csp))->first(fn (string $d): bool => str_starts_with($d, 'script-src '));

    expect($script)->not->toContain('unsafe-inline')->not->toContain('unsafe-eval')->and($csp)->toContain("style-src 'self' 'unsafe-inline'");
});

it('CSP の外部ドメインは、プライバシーポリシーの「外部送信」の表のドメインと一致する', function (): void {
    $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');
    $privacy = (string) file_get_contents(resource_path('legal/privacy.md'));

    foreach (ExternalHosts::listed() as $host) {
        expect($csp)->toContain('https://'.$host);
        expect($privacy)->toContain($host);
    }

    // CSP に書いた外部ドメイン(補助のものを除く)は、すべて表にある
    preg_match_all('#https://([a-z0-9.-]+)#', $csp, $m);
    $listed = ExternalHosts::listed();
    $supporting = array_map(fn (string $o): string => substr($o, strlen('https://')), ExternalHosts::SUPPORTING);
    foreach (array_unique($m[1]) as $host) {
        expect(in_array($host, $listed, true) || in_array($host, $supporting, true))->toBeTrue("{$host} が表にも補助にもありません");
    }

    // 補助のドメインは、表にある事業者(Google)のものだけ
    foreach ($supporting as $host) {
        expect($host)->toMatch('/(google-analytics\.com|analytics\.google\.com|googletagmanager\.com|googlesyndication\.com|doubleclick\.net|adtrafficquality\.google)$/');
    }
});

it('描いたページに、インラインのスクリプトとイベント属性(onclick など)がない', function (): void {
    foreach (['/', '/terms/', '/contact/', '/post/', '/post/spot/', '/admin/login'] as $path) {
        $html = (string) $this->get($path)->getContent();
        // 構造化データ(application/ld+json)は実行されないので除く
        $scripts = preg_replace('#<script type="application/ld\+json">.*?</script>#s', '', $html);
        expect(preg_match('#<script(?![^>]*\ssrc=)#i', (string) $scripts))->toBe(0, "{$path} にインラインのスクリプトがあります");
        expect(preg_match('#\son[a-z]+\s*=\s*["\']#i', $html))->toBe(0, "{$path} にイベント属性があります");
    }
});

it('管理画面の確認ダイアログとセレクトは data 属性で動く(インラインの属性を使わない)', function (): void {
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());
    $html = (string) $this->get('/admin/masters')->getContent();

    expect($html)->toContain('data-autosubmit')->not->toMatch('/\sonchange=/');
    $layout = (string) $this->get('/admin/')->getContent();
    expect($layout)->toContain('data-confirm=')->not->toMatch('/\sonsubmit=/');
});

it('Cookie の同意バナーは公開ページにあり、管理画面にはない。選ぶまで GA4・AdSense のスクリプトは HTML に出ない', function (): void {
    $settings = app(SettingsService::class);
    $settings->set(SettingKey::AnalyticsGa4Id, 'G-TEST12345');
    $settings->set(SettingKey::AdsEnabled, true);
    $settings->set(SettingKey::AdsAdsenseClientId, 'ca-pub-1234567890');

    $html = (string) $this->get('/terms/')->assertOk()->assertSee('data-cookie-banner', false)->assertSee('許可しない')->getContent();
    expect($html)->toContain('<meta name="ga4-id" content="G-TEST12345">')
        ->not->toContain('googletagmanager.com/gtag/js')->not->toContain('adsbygoogle.js')->not->toContain('<script async');

    $this->get('/admin/login')->assertDontSee('data-cookie-banner', false);
});

it('GA4 の ID は正しい形でなければ保存できない(ページに変な値が出ない)', function (): void {
    expect(fn () => app(SettingsService::class)->set(SettingKey::AnalyticsGa4Id, 'G-"><script>alert(1)</script>'))->toThrow(InvalidSettingValueException::class);
    $this->get('/terms/')->assertOk()->assertDontSee('ga4-id', false);
});

it('同意の JavaScript: 初期は拒否、許可で GA4 と AdSense、拒否では GA4 を読まず AdSense は非パーソナライズ。Cookie は1年', function (): void {
    $js = (string) file_get_contents(resource_path('js/consent.js'));

    expect($js)->toContain("ad_storage: 'denied'")->toContain("analytics_storage: 'denied'")->toContain('ad_user_data')->toContain('ad_personalization')
        ->toContain('const YEAR = 60 * 60 * 24 * 365')->toContain('requestNonPersonalizedAds')->toContain('data-consent-ads')->toContain('data-cookie-settings')
        ->toContain('if (granted && ga4');
});
