<?php

declare(strict_types=1);

use App\Support\SafeRedirect;

it('サイト内のパスだけを通す', function (string $path): void {
    expect(SafeRedirect::path($path))->toBe($path);
})->with(['/', '/events/', '/kagawa/events/?date=today&page=2', '/admin/update', '/post/#form']);

it('外部サイトや危険な形は、既定の戻り先にする', function (string $candidate): void {
    expect(SafeRedirect::path($candidate, '/fallback'))->toBe('/fallback');
})->with([
    'https://evil.example/',
    'http://evil.example',
    '//evil.example/path',
    '///evil.example',
    '/\\evil.example',
    '\\evil.example',
    'evil.example',
    'javascript:alert(1)',
    '/redirect?to=https://evil.example',
    "/ok\r\nLocation: https://evil.example",
    "/ok\nx",
    "/\0",
    '',
]);

it('null や長すぎる値は既定にする', function (): void {
    expect(SafeRedirect::path(null))->toBe('/')
        ->and(SafeRedirect::path('/'.str_repeat('a', 600), '/x'))->toBe('/x');
});
