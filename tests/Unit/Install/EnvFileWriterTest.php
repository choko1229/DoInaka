<?php

declare(strict_types=1);

use App\Services\Install\EnvFileWriter;
use Dotenv\Dotenv;

it('どんな記号を含む値も、1つの値として読み戻せる(設定を注入できない)', function (string $value): void {
    $content = (new EnvFileWriter)->render(['DB_PASSWORD' => $value, 'APP_DEBUG' => false, 'DB_PORT' => 3306]);

    $parsed = Dotenv::parse($content);

    expect($parsed)->toHaveCount(3)
        ->and($parsed['DB_PASSWORD'])->toBe($value)
        ->and($parsed['APP_DEBUG'])->toBe('false')
        ->and($parsed['DB_PORT'])->toBe('3306');
})->with([
    'ふつう' => ['p@ssw0rd'],
    '引用符' => ['pa"ss\'word'],
    'ドル記号と波括弧' => ['pa$ss${APP_KEY}word'],
    'バックスラッシュ' => ['pa\\ss\\"word\\'],
    'シャープ(コメント扱いされない)' => ['pass#word # not comment'],
    '等号とセミコロン' => ['a=b;APP_DEBUG=true'],
    '空白' => ['  spaced  '],
    '日本語' => ['パスワード獅子舞'],
    '空文字' => [''],
]);

it('改行・制御文字を含む値は書かない(別の行に設定を書き込めない)', function (string $value): void {
    expect(fn () => (new EnvFileWriter)->render(['DB_PASSWORD' => $value]))->toThrow(InvalidArgumentException::class);
})->with(["a\nAPP_DEBUG=true", "a\rb", "a\0b", "a\x1Fb", "a\x7Fb"]);

it('変数名は大文字・数字・アンダースコアだけ', function (string $name): void {
    expect(fn () => (new EnvFileWriter)->render([$name => 'x']))->toThrow(InvalidArgumentException::class);
})->with(['db_password', 'DB PASSWORD', "DB\nX", '1ABC', 'A=B', '']);

it('ファイルには権限 600 で書く', function (): void {
    $path = sys_get_temp_dir().'/envtest-'.bin2hex(random_bytes(4));

    (new EnvFileWriter)->write($path, ['APP_KEY' => 'base64:abc']);

    expect(fileperms($path) & 0777)->toBe(0600)
        ->and(Dotenv::parse((string) file_get_contents($path)))->toBe(['APP_KEY' => 'base64:abc']);
    unlink($path);
});
