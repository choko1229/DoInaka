<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\User;
use App\Services\Setting\SettingsService;
use App\Services\Url\UrlCanonicalizer;

beforeEach(function (): void {
    config(['app.url' => 'https://xn--gdkt37rmci.net', 'app.canonical_redirects' => true]);
    $this->main = 'https://xn--gdkt37rmci.net';
});

it('do-inaka.net から、同じパスのド田舎.net へ 301 で転送する(クエリも残る)', function (): void {
    $this->rawGet('https://do-inaka.net/kagawa/events/?date=today&q=%E7%8D%85%E5%AD%90')
        ->assertStatus(301)
        ->assertRedirect($this->main.'/kagawa/events/?date=today&q=%E7%8D%85%E5%AD%90');
});

it('http・www 付き・末尾スラッシュなし・大文字は、1回の 301 で正規 URL に着く', function (string $from, string $to): void {
    $response = $this->rawGet($from)->assertStatus(301);
    expect($response->headers->get('Location'))->toBe($to);

    // 転送先は、もう転送されない(1回で済む。試験用のパスなので 404 になる)
    $this->rawGet($to)->assertStatus(404);
})->with([
    'http' => ['http://xn--gdkt37rmci.net/kagawa/', 'https://xn--gdkt37rmci.net/kagawa/'],
    'www' => ['https://www.xn--gdkt37rmci.net/kagawa/', 'https://xn--gdkt37rmci.net/kagawa/'],
    '末尾スラッシュなし' => ['https://xn--gdkt37rmci.net/kagawa/events', 'https://xn--gdkt37rmci.net/kagawa/events/'],
    '大文字' => ['https://xn--gdkt37rmci.net/Kagawa/Events/', 'https://xn--gdkt37rmci.net/kagawa/events/'],
    '共有ドメインで末尾スラッシュなし' => ['https://do-inaka.net/kagawa/events?page=2', 'https://xn--gdkt37rmci.net/kagawa/events/?page=2'],
    'http の共有ドメイン' => ['http://do-inaka.net/kagawa/', 'https://xn--gdkt37rmci.net/kagawa/'],
    'http で www で大文字で末尾なし' => ['http://www.xn--gdkt37rmci.net/Kagawa/Events', 'https://xn--gdkt37rmci.net/kagawa/events/'],
]);

it('知らないホスト名では転送しない', function (): void {
    $this->rawGet('https://evil.example/kagawa/events')->assertStatus(404);
    $this->rawGet('http://localhost/kagawa/events')->assertStatus(404);
});

it('管理画面・API・認証・ファイルの URL は、スラッシュや大文字を触らない(ホストとスキームの転送だけ)', function (): void {
    $canonicalizer = app(UrlCanonicalizer::class);

    foreach (['/admin/update', '/api/v1/regions', '/auth/google/callback', '/install/database', '/sitemap.xml', '/robots.txt', '/build/assets/app.css', '/up'] as $path) {
        expect($canonicalizer->isPage($path))->toBeFalse($path);
    }
    foreach (['/', '/kagawa/', '/kagawa/events/12-shishimai', '/login', '/post/'] as $path) {
        expect($canonicalizer->isPage($path))->toBeTrue($path);
    }

    $this->rawGet('http://xn--gdkt37rmci.net/admin/update')->assertStatus(301)->assertRedirect($this->main.'/admin/update');
    $this->rawGet('https://xn--gdkt37rmci.net/robots.txt')->assertOk();
});

it('POST は転送しない(フォームの送信を壊さない)', function (): void {
    $this->post('https://do-inaka.net/logout')->assertStatus(302);
});

it('共有用の URL は do-inaka.net、正規 URL はメインのドメインを指す', function (): void {
    $canonicalizer = app(UrlCanonicalizer::class);

    expect($canonicalizer->shareUrl('/kagawa/events/12-x/'))->toBe('https://do-inaka.net/kagawa/events/12-x/')
        ->and($canonicalizer->canonicalUrl('/kagawa/', 'page=2'))->toBe('https://xn--gdkt37rmci.net/kagawa/?page=2')
        ->and($canonicalizer->mainHost())->toBe('xn--gdkt37rmci.net');

    app(SettingsService::class)->set(SettingKey::SiteShareHost, 'share.example');
    expect($canonicalizer->shareUrl('/x/'))->toBe('https://share.example/x/');
    $this->rawGet('https://share.example/kagawa/')->assertStatus(301);
    // 古い共有ドメインは、設定を変えたあとは「知らないホスト」になる
    $this->rawGet('https://do-inaka.net/kagawa/')->assertStatus(404);
});

it('APP_URL が http のままでも、https のアクセスを http へ転送しない(HSTS と組み合わさって、転送が終わらなくなる不具合)', function (): void {
    // 本番で、設置を http で行ったため、.env の APP_URL が http:// になっていた
    config(['app.url' => 'http://xn--gdkt37rmci.net']);

    // https で来たら、https の正規 URL へ(http には落とさない)。正規のホスト・スラッシュなら、転送しない
    $this->rawGet('https://xn--gdkt37rmci.net/up')->assertStatus(200);
    $this->rawGet('https://xn--gdkt37rmci.net/kagawa/')->assertStatus(404);
    $this->rawGet('https://www.xn--gdkt37rmci.net/kagawa/')->assertStatus(301)->assertRedirect('https://xn--gdkt37rmci.net/kagawa/');
    $this->rawGet('https://do-inaka.net/kagawa/events')->assertStatus(301)->assertRedirect('https://xn--gdkt37rmci.net/kagawa/events/');

    // http で来たものは、これまでどおり(APP_URL が http なので、http のまま)
    $this->rawGet('http://xn--gdkt37rmci.net/up')->assertStatus(200);
    $this->rawGet('http://www.xn--gdkt37rmci.net/kagawa/')->assertStatus(301)->assertRedirect('http://xn--gdkt37rmci.net/kagawa/');
});

it('転送のくり返し(ループ)にならない: どの入口からでも、転送は多くても1回で、転送先はもう転送されない', function (): void {
    foreach (['http', 'https'] as $appScheme) {
        config(['app.url' => $appScheme.'://xn--gdkt37rmci.net']);
        foreach (['http', 'https'] as $scheme) {
            foreach (['xn--gdkt37rmci.net', 'www.xn--gdkt37rmci.net', 'do-inaka.net'] as $host) {
                foreach (['/kagawa/events', '/kagawa/events/', '/admin/update', '/up'] as $path) {
                    $first = $this->rawGet("{$scheme}://{$host}{$path}");
                    if ($first->status() === 301) {
                        $next = $this->rawGet((string) $first->headers->get('Location'));
                        expect($next->status())->not->toBe(301, "APP_URL={$appScheme}: {$scheme}://{$host}{$path} が、転送先でまた転送された");
                    }
                }
            }
        }
    }
});

it('https で見ているのに APP_URL が http のままなら、管理画面に警告を出す', function (): void {
    config(['app.url' => 'http://xn--gdkt37rmci.net']);
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());

    $this->call('GET', 'https://xn--gdkt37rmci.net/admin/', [], [], [], ['HTTPS' => 'on'])->assertOk()->assertSee('APP_URL が http のままです');
    $this->get('http://xn--gdkt37rmci.net/admin/')->assertOk()->assertDontSee('APP_URL が http のままです');
});
