<?php

declare(strict_types=1);

namespace App\Services\Update;

/**
 * いま動いている版。リリースZIPに入っている VERSION ファイルを読む。
 * 開発環境(VERSION がない)では null で、更新は行わない。
 */
final class CurrentVersion
{
    public function __construct(private readonly string $path) {}

    public function get(): ?Version
    {
        if (! is_file($this->path)) {
            return null;
        }

        return Version::parse(trim((string) file_get_contents($this->path)));
    }
}
