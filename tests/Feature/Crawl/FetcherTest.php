<?php

declare(strict_types=1);

use App\Services\Crawl\FeedReader;
use App\Services\Submission\RobotsChecker;
use App\Services\Web\FetchResult;
use App\Services\Web\HtmlText;
use App\Services\Web\UrlFetcher;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

require_once __DIR__.'/helpers.php';

beforeEach(function (): void {
    fakeDns();
    crawlSource();
});

it('リダイレクトの行き先が社内のアドレスなら、そこへは行かない', function (): void {
    Http::fake([
        'https://city.example/robots.txt' => Http::response('', 404),
        'https://city.example/go' => Http::response('', 302, ['Location' => 'http://internal.test/secret']),
        'http://internal.test/*' => Http::response('SECRET', 200),
    ]);

    $result = app(UrlFetcher::class)->fetch('https://city.example/go');

    expect($result->status)->toBe(FetchResult::FAILED)->and($result->reason)->toBe('unsafe_redirect');
    Http::assertNotSent(fn (Request $r): bool => str_contains((string) $r->url(), 'internal.test'));
});

it('安全なリダイレクトはたどる(行き先の robots.txt も確かめる)。4回以上はたどらない', function (): void {
    Http::fake([
        'https://city.example/robots.txt' => Http::response('', 404),
        'https://city.example/old' => Http::response('', 301, ['Location' => '/new']),
        'https://city.example/new' => Http::response(detailHtml('新しいページ'), 200, ['Content-Type' => 'text/html']),
        'https://city.example/loop*' => Http::response('', 302, ['Location' => '/loop2']),
        'https://city.example/loop2' => Http::response('', 302, ['Location' => '/loop']),
    ]);
    $fetcher = app(UrlFetcher::class);

    $ok = $fetcher->fetch('https://city.example/old');
    expect($ok->ok())->toBeTrue()->and($ok->text())->toContain('新しいページ');

    $loop = $fetcher->fetch('https://city.example/loop');
    expect($loop->status)->toBe(FetchResult::FAILED)->and($loop->reason)->toBe('too_many_redirects');
});

it('robots.txt のリダイレクトは自動でたどらない(読めない = 読まない側に倒す)', function (): void {
    Http::fake([
        'https://city.example/robots.txt' => Http::response('', 302, ['Location' => 'http://internal.test/robots.txt']),
        'http://internal.test/*' => Http::response("User-agent: *\nDisallow:", 200),
    ]);

    expect(app(RobotsChecker::class)->allows('https://city.example/page'))->toBeFalse();
    Http::assertNotSent(fn (Request $r): bool => str_contains((string) $r->url(), 'internal.test'));
});

it('RSS に DOCTYPE・ENTITY があるものは読まない(XXE への備え)。ふつうのフィードは読める', function (): void {
    $evil = '<?xml version="1.0"?><!DOCTYPE rss [<!ENTITY x SYSTEM "file:///etc/passwd">]><rss><channel><item><link>https://city.example/&x;</link></item></channel></rss>';
    expect(FeedReader::links($evil))->toBe([]);

    $atom = '<?xml version="1.0"?><feed xmlns="http://www.w3.org/2005/Atom"><entry><link rel="alternate" href="https://city.example/e/1"/></entry><entry><link href="https://city.example/e/2"/></entry></feed>';
    expect(FeedReader::links($atom))->toBe(['https://city.example/e/1', 'https://city.example/e/2']);
});

it('HTML から本文だけを取り出す(スクリプト・スタイル・ナビは捨てる)。同じサイトのリンクだけ集める', function (): void {
    $html = '<html><head><title>丸亀 &amp; 行事</title><style>.x{}</style></head><body><nav>メニュー</nav><script>alert(1)</script><h1>行事</h1><p>10月に開催。</p><a href="/a">A</a><a href="https://city.example/b#x">B</a><a href="https://evil.example/c">C</a><a href="mailto:x@y">M</a><a href="javascript:alert(1)">J</a></body></html>';

    $text = HtmlText::fromHtml($html);
    expect($text)->toContain('10月に開催。')->not->toContain('alert(1)')->not->toContain('メニュー')->and(HtmlText::title($html))->toBe('丸亀 & 行事');
    expect(HtmlText::sameSiteLinks($html, 'https://city.example/events/'))->toBe(['https://city.example/a', 'https://city.example/b']);
});
