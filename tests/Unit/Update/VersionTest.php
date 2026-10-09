<?php

declare(strict_types=1);

use App\Services\Update\Version;

it('v26.10.1 < v26.10.2 < v26.11.1 < v27.1.1', function (): void {
    $order = ['v26.10.1', 'v26.10.2', 'v26.11.1', 'v27.1.1'];

    foreach ($order as $i => $older) {
        foreach (array_slice($order, $i + 1) as $newer) {
            expect(Version::parse($newer)?->isNewerThan(Version::parse($older) ?? throw new LogicException))->toBeTrue("{$newer} > {$older}")
                ->and(Version::parse($older)?->isNewerThan(Version::parse($newer) ?? throw new LogicException))->toBeFalse();
        }
    }
});

it('各部分を数として比べる(v26.10.10 は v26.10.9 より新しい)', function (): void {
    expect(Version::parse('v26.10.10')?->isNewerThan(Version::parse('v26.10.9') ?? throw new LogicException))->toBeTrue()
        ->and(Version::parse('v26.10.9')?->isNewerThan(Version::parse('v26.10.10') ?? throw new LogicException))->toBeFalse()
        ->and(Version::parse('v26.10.1')?->compare(Version::parse('v26.10.1') ?? throw new LogicException))->toBe(0);
});

it('形の違うタグは版として扱わない', function (string $tag): void {
    expect(Version::parse($tag))->toBeNull();
})->with(['1.0.0', 'v1.0', 'v26.13.1', 'v26.0.1', 'v26.10.0', 'v26.10.1-beta', 'release-26.10.1', 'latest', '', 'V26.10.1', 'v26.10.1 ', 'v2026.10.1']);

it('文字列に戻すと元の形になる', function (): void {
    expect((string) Version::parse('v26.9.3'))->toBe('v26.9.3')
        ->and((string) Version::parse('v26.10.1'))->toBe('v26.10.1');
});
