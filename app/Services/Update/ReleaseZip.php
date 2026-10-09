<?php

declare(strict_types=1);

namespace App\Services\Update;

use RuntimeException;
use ZipArchive;

/**
 * ダウンロードしたリリースZIPの検査と展開。
 *
 * - SHA-256 が GitHub の値と一致しなければ使わない
 * - ZIP でないデータ、必須ファイル(artisan・vendor・VERSION など)が欠けたZIPは使わない
 * - 「../」や絶対パス、シンボリックリンクを含むエントリがあれば、1つも書き出さずに断る(アプリの外に書かせない)
 */
final class ReleaseZip
{
    /** 配布版に必ずあるファイル */
    public const REQUIRED = ['artisan', 'composer.json', 'vendor/autoload.php', 'public/index.php', 'public/.htaccess', 'bootstrap/app.php', 'VERSION'];

    public function verifyChecksum(string $zipPath, ?string $expectedSha256): void
    {
        if ($expectedSha256 === null) {
            throw new RuntimeException(__('update.no_digest'));
        }

        $actual = hash_file('sha256', $zipPath);
        if ($actual === false || ! hash_equals(strtolower($expectedSha256), $actual)) {
            throw new RuntimeException(__('update.digest_mismatch'));
        }
    }

    /**
     * @throws RuntimeException 使えないZIPのとき
     */
    public function inspect(string $zipPath, Version $expected): void
    {
        $zip = $this->open($zipPath);

        try {
            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                $this->assertSafeEntry($zip, $i, $name);
                $names[$name] = true;
            }

            foreach (self::REQUIRED as $required) {
                if (! isset($names[$required])) {
                    throw new RuntimeException(__('update.zip_missing_file', ['file' => $required]));
                }
            }

            $version = Version::parse(trim((string) $zip->getFromName('VERSION')));
            if ($version === null || $version->compare($expected) !== 0) {
                throw new RuntimeException(__('update.zip_version_mismatch'));
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * 検査済みのZIPを、空のディレクトリへ展開する。
     */
    public function extract(string $zipPath, string $destination): int
    {
        $zip = $this->open($zipPath);
        $count = 0;

        try {
            // 先に全エントリを確かめてから書き出す(途中まで書いて止まらない)
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $this->assertSafeEntry($zip, $i, (string) $zip->getNameIndex($i));
            }

            if (! is_dir($destination) && ! mkdir($destination, 0775, true) && ! is_dir($destination)) {
                throw new RuntimeException(__('update.cannot_write', ['path' => basename($destination)]));
            }
            $root = (string) realpath($destination);

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                $target = $root.'/'.$name;

                if (str_ends_with($name, '/')) {
                    $this->makeDirectory($target);

                    continue;
                }

                $this->makeDirectory(dirname($target));
                $in = $zip->getStream($name);
                $out = fopen($target, 'wb');
                if ($in === false || $out === false) {
                    throw new RuntimeException(__('update.cannot_write', ['path' => $name]));
                }
                stream_copy_to_stream($in, $out);
                fclose($in);
                fclose($out);
                $count++;
            }
        } finally {
            $zip->close();
        }

        return $count;
    }

    private function open(string $zipPath): ZipArchive
    {
        $zip = new ZipArchive;
        if (! is_file($zipPath) || $zip->open($zipPath, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException(__('update.not_a_zip'));
        }

        return $zip;
    }

    private function assertSafeEntry(ZipArchive $zip, int $index, string $name): void
    {
        $unsafe = $name === ''
            || str_contains($name, "\0")
            || str_contains($name, '\\')
            || str_starts_with($name, '/')
            || preg_match('/^[A-Za-z]:/', $name) === 1
            || in_array('..', explode('/', $name), true);

        if (! $unsafe) {
            // Unix のシンボリックリンク(0120000)は断る
            $opsys = 0;
            $attributes = 0;
            $found = $zip->getExternalAttributesIndex($index, $opsys, $attributes);
            $mode = is_int($attributes) ? ($attributes >> 16) & 0170000 : 0;
            if ($found && $opsys === ZipArchive::OPSYS_UNIX && $mode === 0120000) {
                $unsafe = true;
            }
        }

        if ($unsafe) {
            throw new RuntimeException(__('update.zip_unsafe_entry', ['name' => mb_substr($name, 0, 80)]));
        }
    }

    private function makeDirectory(string $path): void
    {
        if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
            throw new RuntimeException(__('update.cannot_write', ['path' => basename($path)]));
        }
    }
}
