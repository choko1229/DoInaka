<?php

declare(strict_types=1);

namespace App\Services\Install;

use Illuminate\Support\Str;

/**
 * 設置キー(FTP などでサーバーのファイルを見られる人だけが設置できるようにする)。
 *
 * 初回アクセスで公開外の storage にランダムなキーを書き出し、画面でその入力を求める。設置が終わったら消す。
 */
final class InstallKey
{
    public function __construct(private readonly string $path) {}

    public function path(): string
    {
        return $this->path;
    }

    /** なければ作る(初回アクセス) */
    public function ensure(): void
    {
        if (is_file($this->path)) {
            return;
        }

        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0775, true);
        }

        file_put_contents($this->path, Str::lower(Str::random(24)));
        @chmod($this->path, 0600);
    }

    public function verify(string $input): bool
    {
        if (! is_file($this->path)) {
            return false;
        }

        $key = trim((string) file_get_contents($this->path));

        return $key !== '' && hash_equals($key, trim($input));
    }

    public function forget(): void
    {
        if (is_file($this->path)) {
            @unlink($this->path);
        }
    }
}
