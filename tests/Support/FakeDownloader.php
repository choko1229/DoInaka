<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\ReleaseDownloader;
use RuntimeException;

final class FakeDownloader implements ReleaseDownloader
{
    public int $downloads = 0;

    public bool $fail = false;

    public function __construct(public string $source) {}

    public function download(string $url, string $destination): void
    {
        $this->downloads++;

        if ($this->fail) {
            throw new RuntimeException('接続できません');
        }

        copy($this->source, $destination);
    }
}
