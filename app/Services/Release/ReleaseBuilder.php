<?php

declare(strict_types=1);

namespace App\Services\Release;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

/**
 * 配布用のリリースZIPを作る(実装指示書 フェーズ0・1)。
 *
 * - vendor(本番用)とビルド済みの public/build、VERSION、public/.htaccess を含める
 * - .env、tests、docs、開発用ファイル、元のイラスト PNG、キャッシュ・ログ・セッションは含めない
 * - 作ったあとに中身を検査し、入ってはいけないものが1つでもあれば ZIP を消して失敗にする
 *
 * 呼び出す前に `composer install --no-dev` と `npm run build` を済ませておく。
 */
final class ReleaseBuilder
{
    /** ZIP に入れるディレクトリ(ルートからの相対パス) */
    private const INCLUDE_DIRS = ['app', 'bootstrap', 'config', 'database', 'lang', 'public', 'resources/views', 'routes', 'vendor'];

    /** ZIP に入れるファイル */
    private const INCLUDE_FILES = ['artisan', 'composer.json', 'composer.lock'];

    /** 空のまま配るディレクトリ。中身は入れず、.gitignore だけ入れて形を保つ */
    private const SKELETON_DIRS = ['storage', 'bootstrap/cache'];

    /** どのディレクトリにあっても入れないファイル名のパターン */
    private const EXCLUDED_NAMES = [
        '/^\.env(\..*)?$/',
        '/^\.DS_Store$/',
        '/^Thumbs\.db$/',
        '/^database\.sqlite$/',
        '/\.log$/',
        '/\.phpunit\.result\.cache$/',
        '/^\.git(ignore|attributes|keep)?$/',
    ];

    /** 入れないパス(ルートからの相対。先頭一致) */
    private const EXCLUDED_PREFIXES = ['public/hot', 'public/storage', 'bootstrap/cache/', 'database/factories', 'resources/images/illust/src'];

    /** 完成した ZIP の中に1つでもあってはいけないパス */
    private const FORBIDDEN_IN_ZIP = [
        '/(^|\/)\.env(\..*)?$/',
        '/^tests\//',
        '/^docs\//',
        '/^\.git/',
        '/^\.github\//',
        '/^\.claude\//',
        '/^docker/',
        '/^node_modules\//',
        '/^CLAUDE\.md$/',
        '/^phpunit\.xml$/',
        '/^phpstan\.neon$/',
        '/^pint\.json$/',
        '/^docker-compose\.yml$/',
        '/^resources\/images\/illust\/src\//',
        '/\.log$/',
        '/(^|\/)\.\.(\/|$)/',
        '/^\//',
    ];

    public function __construct(private readonly string $basePath) {}

    /**
     * ZIP を作って、中に入れたファイル数を返す。
     */
    public function build(string $zipPath, string $version): int
    {
        $base = rtrim($this->basePath, '/\\');

        if (! is_file($base.'/public/build/manifest.json')) {
            throw new RuntimeException(__('release.missing_build'));
        }

        if (! is_dir($base.'/vendor')) {
            throw new RuntimeException(__('release.missing_vendor'));
        }

        $directory = dirname($zipPath);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException(__('release.cannot_write', ['path' => $directory]));
        }
        if (is_file($zipPath)) {
            unlink($zipPath);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException(__('release.cannot_write', ['path' => $zipPath]));
        }

        $count = 0;

        foreach (self::INCLUDE_FILES as $file) {
            if (is_file($base.'/'.$file)) {
                $zip->addFile($base.'/'.$file, $file);
                $count++;
            }
        }

        foreach (self::INCLUDE_DIRS as $dir) {
            $count += $this->addDirectory($zip, $base, $dir);
        }

        foreach (self::SKELETON_DIRS as $dir) {
            $count += $this->addSkeleton($zip, $base, $dir);
        }

        // 配布版の目印。更新の確認と管理画面の表示に使う
        $zip->addFromString('VERSION', $version."\n");
        $count++;

        $zip->close();

        $this->assertClean($zipPath);

        return $count;
    }

    private function addDirectory(ZipArchive $zip, string $base, string $relative): int
    {
        $root = $base.'/'.$relative;
        if (! is_dir($root)) {
            return 0;
        }

        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $path = $this->relativePath($base, $file->getPathname());
            if ($this->isExcluded($path, $file->getFilename())) {
                continue;
            }

            $zip->addFile($file->getPathname(), $path);
            $count++;
        }

        return $count;
    }

    /**
     * storage と bootstrap/cache は、ディレクトリの形(.gitignore)だけを入れる。
     */
    private function addSkeleton(ZipArchive $zip, string $base, string $relative): int
    {
        $root = $base.'/'.$relative;
        if (! is_dir($root)) {
            return 0;
        }

        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === '.gitignore') {
                $zip->addFile($file->getPathname(), $this->relativePath($base, $file->getPathname()));
                $count++;
            }
        }

        return $count;
    }

    private function relativePath(string $base, string $absolute): string
    {
        $relative = substr(str_replace('\\', '/', $absolute), strlen(str_replace('\\', '/', $base)) + 1);

        return ltrim($relative, '/');
    }

    private function isExcluded(string $relative, string $filename): bool
    {
        // .gitignore は storage などの形を保つために、ディレクトリ単位の例外として別処理にしている。ここでは入れない
        foreach (self::EXCLUDED_NAMES as $pattern) {
            if (preg_match($pattern, $filename) === 1) {
                return true;
            }
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 完成した ZIP に入ってはいけないものがないか確かめ、あれば ZIP を消して例外にする。
     */
    private function assertClean(string $zipPath): void
    {
        $zip = new ZipArchive;
        $zip->open($zipPath);
        $problems = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            foreach (self::FORBIDDEN_IN_ZIP as $pattern) {
                if (preg_match($pattern, $name) === 1) {
                    $problems[] = $name;
                    break;
                }
            }
        }

        $hasHtaccess = $zip->locateName('public/.htaccess') !== false;
        $zip->close();

        if ($problems !== [] || ! $hasHtaccess) {
            unlink($zipPath);

            throw new RuntimeException(__('release.unclean', [
                'files' => implode(', ', array_slice($problems, 0, 5)).($hasHtaccess ? '' : ' (public/.htaccess がありません)'),
            ]));
        }
    }
}
