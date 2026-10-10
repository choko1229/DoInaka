<?php

declare(strict_types=1);

use App\Services\Auth\Totp;

/** RFC 6238 の付録B(SHA-1)の試験値。秘密鍵は ASCII の "12345678901234567890" */
const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

it('RFC 6238 の試験値と一致する(6桁)', function (int $time, string $expected): void {
    $totp = new Totp;

    expect($totp->codeAt(RFC_SECRET, $totp->timestepAt($time)))->toBe($expected);
})->with([
    [59, '287082'],
    [1111111109, '081804'],
    [1111111111, '050471'],
    [1234567890, '005924'],
    [2000000000, '279037'],
    [20000000000, '353130'],
]);

it('時間枠の前後1つ(±30秒)のコードは通り、2つ以上ずれたコードは通らない', function (): void {
    $totp = new Totp;
    $now = 1_800_000_000;
    $step = $totp->timestepAt($now);

    expect($totp->verify(RFC_SECRET, $totp->codeAt(RFC_SECRET, $step), $now))->toBe($step)
        ->and($totp->verify(RFC_SECRET, $totp->codeAt(RFC_SECRET, $step - 1), $now))->toBe($step - 1)
        ->and($totp->verify(RFC_SECRET, $totp->codeAt(RFC_SECRET, $step + 1), $now))->toBe($step + 1)
        ->and($totp->verify(RFC_SECRET, $totp->codeAt(RFC_SECRET, $step - 2), $now))->toBeNull()
        ->and($totp->verify(RFC_SECRET, $totp->codeAt(RFC_SECRET, $step + 2), $now))->toBeNull();
});

it('同じコード(同じか古い時間枠)の使い回しを拒む', function (): void {
    $totp = new Totp;
    $now = 1_800_000_000;
    $step = $totp->timestepAt($now);
    $code = $totp->codeAt(RFC_SECRET, $step);

    $used = $totp->verify(RFC_SECRET, $code, $now);
    expect($used)->toBe($step)
        ->and($totp->verify(RFC_SECRET, $code, $now, $used))->toBeNull()
        ->and($totp->verify(RFC_SECRET, $totp->codeAt(RFC_SECRET, $step - 1), $now, $used))->toBeNull()
        ->and($totp->verify(RFC_SECRET, $totp->codeAt(RFC_SECRET, $step + 1), $now, $used))->toBe($step + 1);
});

it('桁・文字が不正な入力は通さない(空白とハイフンは無視する)', function (): void {
    $totp = new Totp;
    $now = 1_800_000_000;
    $code = $totp->codeAt(RFC_SECRET, $totp->timestepAt($now));

    foreach (['', '12345', '1234567', 'abcdef', "{$code}x", '000000 000000'] as $bad) {
        expect($totp->verify(RFC_SECRET, $bad, $now))->toBeNull();
    }
    expect($totp->verify(RFC_SECRET, substr($code, 0, 3).' '.substr($code, 3), $now))->not->toBeNull()
        ->and($totp->verify(RFC_SECRET, substr($code, 0, 3).'-'.substr($code, 3), $now))->not->toBeNull();
});

it('秘密鍵は20バイト(32文字の Base32)で、毎回変わり、往復できる', function (): void {
    $totp = new Totp;
    $a = $totp->generateSecret();

    expect($a)->toMatch('/^[A-Z2-7]{32}$/')
        ->and($totp->generateSecret())->not->toBe($a)
        ->and($totp->base32Encode($totp->base32Decode($a)))->toBe($a)
        ->and($totp->base32Decode(RFC_SECRET))->toBe('12345678901234567890');
});

it('Base32 に使えない文字は拒む', function (): void {
    expect(fn () => (new Totp)->base32Decode('AB1!'))->toThrow(InvalidArgumentException::class);
});

it('認証アプリ用の URI と、手入力用の区切りを作る', function (): void {
    $totp = new Totp;
    $uri = $totp->uri('JBSWY3DPEHPK3PXP', 'ちょこ', 'ド田舎.net');

    expect($uri)->toStartWith('otpauth://totp/')
        ->and($uri)->toContain('secret=JBSWY3DPEHPK3PXP')
        ->and($uri)->toContain('issuer='.rawurlencode('ド田舎.net'))
        ->and($uri)->toContain('digits=6')
        ->and($uri)->toContain('period=30')
        ->and($totp->format('JBSWY3DPEHPK3PXP'))->toBe('JBSW Y3DP EHPK 3PXP');
});
