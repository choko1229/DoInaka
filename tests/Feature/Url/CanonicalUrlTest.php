<?php

declare(strict_types=1);

use App\Enums\SettingKey;
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
