<?php

declare(strict_types=1);

namespace App\Services\Update;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

/**
 * 更新の前に取るバックアップ(DBのダンプとコードのZIP)。直近 N 世代だけ残す。
 *
 * コードのZIPには storage(投稿画像・ログ・バックアップ自身)と vendor を入れない。
 * 投稿画像は kagoya の自動バックアップに任せ、vendor は同じ版のリリースZIPから戻せる(設計書1章)。
 */
class BackupStore
{
    private const CODE_EXCLUDES = ['storage', 'vendor', 'node_modules', '.git', 'dist'];

    public function __construct(
        private readonly string $directory,
        private readonly int $keep,
        private readonly SqlDumper $dumper,
    ) {}

    /**
     * @return string 作ったバックアップのディレクトリ
     */
    public function create(Connection $db, string $appPath, string $label): string
    {
        if (preg_match('/^[A-Za-z0-9._-]+$/', $label) !== 1) {
            throw new RuntimeException(__('update.invalid_label'));
        }

        $path = $this->directory.'/'.date('YmdHis').'-'.$label;
        if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new RuntimeException(__('update.cannot_write', ['path' => basename($path)]));
        }

        $this->dumper->dump($db, $path.'/db.sql');
        $this->zipCode($appPath, $path.'/code.zip');
        $this->prune();

        return $path;
    }

    /**
     * 新しい順のバックアップ一覧。
     *
     * @return list<array{path: string, name: string, db_bytes: int, code_bytes: int, created_at: int}>
     */
    public function list(): array
    {
        $items = [];
        foreach (glob($this->directory.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $items[] = [
                'path' => $dir,
                'name' => basename($dir),
                'db_bytes' => (int) @filesize($dir.'/db.sql'),
                'code_bytes' => (int) @filesize($dir.'/code.zip'),
                'created_at' => (int) @filemtime($dir),
            ];
        }
        usort($items, fn (array $a, array $b): int => strcmp($b['name'], $a['name']));

        return $items;
    }

    public function prune(): void
    {
        foreach (array_slice($this->list(), $this->keep) as $old) {
            File::deleteDirectory($old['path']);
        }
    }

    private function zipCode(string $appPath, string $zipPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException(__('update.cannot_write', ['path' => basename($zipPath)]));
        }

        $root = rtrim(str_replace('\\', '/', $appPath), '/');
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }
            $relative = ltrim(substr(str_replace('\\', '/', $file->getPathname()), strlen($root)), '/');
            if (in_array(explode('/', $relative)[0], self::CODE_EXCLUDES, true)) {
                continue;
            }
            $zip->addFile($file->getPathname(), $relative);
        }

        $zip->close();
    }
}
