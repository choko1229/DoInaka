<?php

declare(strict_types=1);

use App\Services\Update\GitHubReleaseSource;
use Illuminate\Support\Facades\Http;

function ghRelease(string $tag, array $overrides = []): array
{
    return array_merge([
        'tag_name' => $tag,
        'name' => $tag,
        'draft' => false,
        'prerelease' => false,
        'body' => '変更',
        'html_url' => 'https://github.com/choko1229/doinaka/releases/tag/'.$tag,
        'published_at' => '2026-10-07T01:02:03Z',
        'assets' => [[
            'name' => "doinaka-{$tag}.zip",
            'browser_download_url' => "https://github.com/choko1229/doinaka/releases/download/{$tag}/doinaka-{$tag}.zip",
            'size' => 1234,
            'digest' => 'sha256:'.str_repeat('AB', 32),
        ]],
    ], $overrides);
}

it('タグが版の形で、ZIP が添付されたリリースだけを新しい順に返す', function (): void {
    Http::fake(['api.github.com/*' => Http::response([
        ghRelease('v26.10.1'),
        ghRelease('v26.10.10'),
        ghRelease('v26.10.9'),
        ghRelease('nightly'),
        ghRelease('v26.10.5', ['draft' => true]),
        ghRelease('v26.10.6', ['assets' => []]),
        ghRelease('v26.10.7', ['assets' => [['name' => 'source.tar.gz', 'browser_download_url' => 'https://github.com/x/y/a.tar.gz']]]),
    ])]);

    $releases = (new GitHubReleaseSource)->releases('choko1229/doinaka');

    expect(array_map(fn ($r): string => (string) $r->version, $releases))->toBe(['v26.10.10', 'v26.10.9', 'v26.10.1']);
});

it('SHA-256 の digest を小文字で読み、プレリリースかどうかも読む', function (): void {
    Http::fake(['api.github.com/*' => Http::response([ghRelease('v26.10.2', ['prerelease' => true, 'name' => '【BETA】v26.10.2'])])]);

    $release = (new GitHubReleaseSource)->releases('choko1229/doinaka')[0];

    expect($release->sha256)->toBe(str_repeat('ab', 32))
        ->and($release->prerelease)->toBeTrue()
        ->and($release->name)->toBe('【BETA】v26.10.2')
        ->and($release->size)->toBe(1234)
        ->and($release->publishedAt?->toDateString())->toBe('2026-10-07');
});

it('digest がなければ sha256 は null(適用側が拒否する)', function (): void {
    $release = ghRelease('v26.10.2');
    unset($release['assets'][0]['digest']);
    Http::fake(['api.github.com/*' => Http::response([$release])]);

    expect((new GitHubReleaseSource)->releases('choko1229/doinaka')[0]->sha256)->toBeNull();
});

it('GitHub 以外のダウンロード URL は使わない', function (): void {
    $release = ghRelease('v26.10.2');
    $release['assets'][0]['browser_download_url'] = 'https://evil.example/doinaka-v26.10.2.zip';
    Http::fake(['api.github.com/*' => Http::response([$release])]);

    expect((new GitHubReleaseSource)->releases('choko1229/doinaka'))->toBe([]);
});

it('トークンを付けずに、公開 API を呼ぶ', function (): void {
    Http::fake(['api.github.com/*' => Http::response([])]);

    (new GitHubReleaseSource)->releases('choko1229/doinaka');

    Http::assertSent(fn ($request): bool => $request->url() !== '' && ! $request->hasHeader('Authorization') && str_contains($request->url(), 'repos/choko1229/doinaka/releases'));
});

it('リポジトリ名の形が正しくなければ呼ばない', function (): void {
    Http::fake();

    expect(fn () => (new GitHubReleaseSource)->releases('../evil?x=1'))->toThrow(RuntimeException::class);
    Http::assertNothingSent();
});

it('GitHub がエラーを返したら例外', function (): void {
    Http::fake(['api.github.com/*' => Http::response('rate limit', 403)]);

    expect(fn () => (new GitHubReleaseSource)->releases('choko1229/doinaka'))->toThrow(RuntimeException::class);
});
