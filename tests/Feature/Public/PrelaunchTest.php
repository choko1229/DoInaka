<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\SettingKey;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Inquiry;
use App\Models\Submission;
use App\Models\User;
use App\Services\Setting\SettingsService;

function prelaunch(bool $on): void
{
    app(SettingsService::class)->set(SettingKey::SitePrelaunch, $on);
}

beforeEach(fn () => prelaunch(true));

it('既定はオフ(すでに公開している設置には影響しない)', function (): void {
    app(SettingsService::class)->forget(SettingKey::SitePrelaunch);

    expect(SettingKey::SitePrelaunch->default())->toBeFalse();
    $this->get('/terms/')->assertOk();
});

it('オンのとき、未ログイン・会員・2段階認証前の管理者には「準備中」(503 と Retry-After)', function (): void {
    foreach (['/', '/terms/', '/privacy/', '/contact/', '/post/', '/kagawa/'] as $path) {
        $this->get($path)->assertStatus(503)->assertHeader('Retry-After', '3600')->assertSee('ただいま準備中です')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    $this->actingAs(User::factory()->create())->get('/')->assertStatus(503)->assertSee('ただいま準備中です');
    // 管理者でも、2段階認証を通る前は準備中
    $this->actingAs(User::factory()->admin()->twoFactor()->create())->get('/')->assertStatus(503);
    // 2段階認証が未設定の管理者も同じ
    $this->actingAs(User::factory()->admin()->create())->get('/')->assertStatus(503);
});

it('オンでも、2段階認証まで済んだ管理者・編集者は普段どおり見られる', function (): void {
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());
    $this->get('/terms/')->assertOk()->assertSee('第1条')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $this->actingAsVerifiedAdmin(User::factory()->twoFactor()->create(['role' => UserRole::Editor]));
    $this->get('/terms/')->assertOk();
    $this->get('/contact/')->assertOk();
});

it('オンでも、インストーラー・ログイン・コールバック・管理画面・ヘルスチェックは通る', function (): void {
    $this->get('/login')->assertOk();
    $this->get('/admin/login')->assertOk();
    $this->get('/admin')->assertRedirect();
    $this->get('/up')->assertOk();
    foreach (['/install/', '/auth/google', '/auth/google/callback'] as $path) {
        expect($this->get($path)->status())->not->toBe(503);
    }
});

it('オンのとき、投稿・修正依頼・お問い合わせ・コメント・お気に入りの送信はすべて断られ、何も保存されない', function (): void {
    $this->post('/post/spot/', ['title' => 'x', 'body' => 'y', 'consent_terms' => '1', 'consent_overseas' => '1'])->assertStatus(503);
    $this->post('/post/tip/', ['source_url' => 'https://example.com/', 'consent_terms' => '1', 'consent_overseas' => '1'])->assertStatus(503);
    $this->post('/report/spot/1/', ['field' => 'title', 'proposed_value' => 'z', 'consent_terms' => '1'])->assertStatus(503);
    $this->post('/contact/', ['kind' => 'general', 'body' => 'こんにちは', 'consent_terms' => '1', 'consent_overseas' => '1'])->assertStatus(503);
    $this->postJson('/api/v1/spot/1/comments', ['body' => 'こんにちは'])->assertStatus(503)->assertJsonPath('message', 'ただいま準備中です。');
    $this->postJson('/api/v1/favorites/spot/1')->assertStatus(503);
    $this->actingAs(User::factory()->create())->post('/post/article/', ['title' => 'x', 'body' => 'y'])->assertStatus(503);

    expect(Submission::query()->count())->toBe(0)->and(Inquiry::query()->count())->toBe(0);
});

it('オンのとき、robots.txt は全体を Disallow、サイトマップは空', function (): void {
    $robots = $this->get('/robots.txt')->assertOk()->getContent();
    expect($robots)->toContain("Disallow: /\n")->not->toContain('Sitemap:')->not->toContain('Allow');

    $this->get('/sitemap.xml')->assertOk()->assertDontSee('<loc>', false)->assertSee('sitemapindex', false);
    $this->get('/sitemap-spots.xml')->assertOk()->assertDontSee('<loc>', false)->assertSee('urlset', false);
});

it('オンのとき、すべての応答(管理画面・ログイン・robots.txt も)に X-Robots-Tag: noindex, nofollow が付く', function (): void {
    foreach (['/', '/login', '/admin/login', '/robots.txt', '/sitemap.xml', '/no-such-page-xyz/'] as $path) {
        $this->get($path)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());
    $this->get('/admin/')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('管理画面のダッシュボードと管理者バーに「公開前モード中」と出る', function (): void {
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());

    $this->get('/admin/')->assertOk()->assertSee('公開前モード中');
    $this->get('/admin/bar')->assertOk()->assertSee('公開前モード中');

    prelaunch(false);
    $this->get('/admin/')->assertOk()->assertDontSee('公開前モード中');
    $this->get('/admin/bar')->assertOk()->assertDontSee('公開前モード中');
});

it('オフに戻すと元どおり(誰でも見られ、送信でき、robots.txt とサイトマップも通常、X-Robots-Tag なし)', function (): void {
    prelaunch(false);

    $this->get('/terms/')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    $this->get('/')->assertOk();
    $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:')->assertDontSee("Disallow: /\n", false);
    $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>', false);
    $this->post('/contact/', ['kind' => 'general', 'body' => 'こんにちは', 'consent_terms' => '1', 'consent_overseas' => '1'])->assertRedirect('/contact/done/');
    expect(Inquiry::query()->count())->toBe(1);
});

it('設定の「サイト」タブで切り替えられ、切り替えは操作ログに残る(前後の値つき)', function (): void {
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());
    $site = ['site__name' => 'ド田舎.net', 'site__description' => '説明', 'site__operator' => '', 'site__share_host' => 'do-inaka.net'];

    $this->get('/admin/settings/site')->assertOk()->assertSee('公開前モード')->assertDontSee('settings.keys');
    $this->post('/admin/settings/site', $site)->assertRedirect('/admin/settings/site');

    expect(app(SettingsService::class)->bool(SettingKey::SitePrelaunch))->toBeFalse();
    $log = AuditLog::query()->where('action', AuditAction::SettingsChange->value)->where('target_id', 'site.prelaunch')->firstOrFail();
    expect($log->detail)->toMatchArray(['before' => true, 'after' => false]);

    $this->post('/admin/settings/site', $site + ['site__prelaunch' => '1'])->assertRedirect();
    expect(app(SettingsService::class)->bool(SettingKey::SitePrelaunch))->toBeTrue()
        ->and(AuditLog::query()->where('target_id', 'site.prelaunch')->count())->toBe(2);
});
