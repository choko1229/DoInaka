<?php

declare(strict_types=1);

use App\Services\Release\ReleaseBuilder;
use Illuminate\Support\Facades\File;

/**
 * 本物のプロジェクトに似せた小さな作業ツリーを作る。開発用のファイルや秘密の値も混ぜておく。
 */
function makeProjectTree(): string
{
    $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'doinaka-release-'.bin2hex(random_bytes(4));
    $files = [
        'artisan' => '#!/usr/bin/env php',
        'composer.json' => '{}',
        'composer.lock' => '{}',
        'app/Models/Setting.php' => '<?php',
        'bootstrap/app.php' => '<?php',
        'bootstrap/cache/packages.php' => '<?php // キャッシュ',
        'bootstrap/cache/.gitignore' => "*\n!.gitignore\n",
        'config/app.php' => '<?php',
        'database/migrations/2026_01_01_000000_x.php' => '<?php',
        'database/database.sqlite' => 'sqlite',
        'database/factories/UserFactory.php' => '<?php',
        'lang/ja/layout.php' => '<?php',
        'public/.htaccess' => 'php_value memory_limit 256M',
        'public/index.php' => '<?php',
        'public/hot' => 'http://localhost:5173',
        'public/build/manifest.json' => '{}',
        'public/build/assets/app-abc.css' => 'body{}',
        'resources/views/public/home.blade.php' => 'home',
        'resources/prompts/review_text.md' => 'prompt',
        'resources/legal/privacy.md' => 'privacy',
        'resources/css/app.css' => 'body{}',
        'resources/images/illust/src/island-summer-evening.png' => 'PNG',
        'routes/web.php' => '<?php',
        'vendor/autoload.php' => '<?php',
        'vendor/laravel/framework/src/x.php' => '<?php',
        'vendor/some/package/.env.example' => 'SECRET=1',
        'vendor/some/package/.gitignore' => 'x',
        'storage/logs/laravel.log' => 'ログ',
        'storage/logs/.gitignore' => "*\n!.gitignore\n",
        'storage/framework/sessions/abc' => 'セッション',
        'storage/framework/sessions/.gitignore' => "*\n!.gitignore\n",
        'storage/app/private/backup.sql' => 'ダンプ',
        '.env' => 'APP_KEY=base64:secret',
        '.env.local' => 'OPENROUTER_API_KEY=sk-secret',
        '.env.example' => 'APP_KEY=',
        'tests/Feature/ExampleTest.php' => '<?php',
        'docs/implementation.md' => '# 指示書',
        'CLAUDE.md' => '# rules',
        '.claude/settings.json' => '{}',
        '.github/workflows/ci.yml' => 'name: ci',
        'docker/app/Dockerfile' => 'FROM php',
        'docker-compose.yml' => 'services:',
        'phpunit.xml' => '<phpunit/>',
        'node_modules/x/index.js' => '',
    ];

    foreach ($files as $path => $content) {
        $full = $root.'/'.$path;
        if (! is_dir(dirname($full))) {
            mkdir(dirname($full), 0775, true);
        }
        file_put_contents($full, $content);
    }

    return $root;
}

/**
 * @return list<string>
 */
function zipEntries(string $zipPath): array
{
    $zip = new ZipArchive;
    $zip->open($zipPath);
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = (string) $zip->getNameIndex($i);
    }
    $zip->close();
    sort($names);

    return $names;
}

beforeEach(function (): void {
    $this->root = makeProjectTree();
    $this->zip = $this->root.'/dist/doinaka-v26.10.1.zip';
});

afterEach(function (): void {
    File::deleteDirectory($this->root);
});

it('リリースZIPを展開すると、.env と tests と開発用ファイルが入っていない', function (): void {
    (new ReleaseBuilder($this->root))->build($this->zip, 'v26.10.1');

    $entries = zipEntries($this->zip);

    foreach ($entries as $name) {
        expect($name)
            ->not->toMatch('/(^|\/)\.env(\..*)?$/')
            ->not->toStartWith('tests/')
            ->not->toStartWith('docs/')
            ->not->toStartWith('.github/')
            ->not->toStartWith('.claude/')
            ->not->toStartWith('docker')
            ->not->toStartWith('node_modules/')
            ->not->toBe('CLAUDE.md')
            ->not->toBe('phpunit.xml')
            ->not->toContain('database.sqlite')
            ->not->toEndWith('.log');
    }
});

it('必要なものは入っている(vendor、ビルド済みアセット、VERSION、public/.htaccess)', function (): void {
    (new ReleaseBuilder($this->root))->build($this->zip, 'v26.10.1');

    expect(zipEntries($this->zip))->toContain(
        'artisan', 'composer.json', 'composer.lock', 'VERSION',
        'app/Models/Setting.php', 'bootstrap/app.php', 'config/app.php', 'routes/web.php',
        'lang/ja/layout.php', 'resources/views/public/home.blade.php', 'resources/prompts/review_text.md', 'resources/legal/privacy.md',
        'database/migrations/2026_01_01_000000_x.php',
        'public/.htaccess', 'public/index.php', 'public/build/manifest.json', 'public/build/assets/app-abc.css',
        'vendor/autoload.php', 'vendor/laravel/framework/src/x.php',
    );
});

it('VERSION に版が書かれている', function (): void {
    (new ReleaseBuilder($this->root))->build($this->zip, 'v26.10.1');

    $zip = new ZipArchive;
    $zip->open($this->zip);
    expect(trim((string) $zip->getFromName('VERSION')))->toBe('v26.10.1');
    $zip->close();
});

it('キャッシュ・ログ・セッション・バックアップ・元のイラスト PNG は入れず、storage の形だけ残す', function (): void {
    (new ReleaseBuilder($this->root))->build($this->zip, 'v26.10.1');

    $entries = zipEntries($this->zip);

    expect($entries)
        ->not->toContain('bootstrap/cache/packages.php')
        ->not->toContain('storage/logs/laravel.log')
        ->not->toContain('storage/framework/sessions/abc')
        ->not->toContain('storage/app/private/backup.sql')
        ->not->toContain('public/hot')
        ->not->toContain('resources/images/illust/src/island-summer-evening.png')
        ->toContain('storage/logs/.gitignore', 'storage/framework/sessions/.gitignore', 'bootstrap/cache/.gitignore');
});

it('ビルド済みアセットがないと作らない', function (): void {
    unlink($this->root.'/public/build/manifest.json');

    expect(fn () => (new ReleaseBuilder($this->root))->build($this->zip, 'v26.10.1'))
        ->toThrow(RuntimeException::class);
    expect(is_file($this->zip))->toBeFalse();
});

it('public/.htaccess がないと、できた ZIP を消して失敗にする', function (): void {
    unlink($this->root.'/public/.htaccess');

    expect(fn () => (new ReleaseBuilder($this->root))->build($this->zip, 'v26.10.1'))
        ->toThrow(RuntimeException::class);
    expect(is_file($this->zip))->toBeFalse();
});

it('artisan release:build は版の形が正しくないと失敗する', function (): void {
    $this->artisan('release:build', ['--release-version' => '1.0.0'])->assertFailed();
});
