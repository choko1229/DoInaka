<?php

declare(strict_types=1);

namespace App\Services\Update;

use Carbon\CarbonImmutable;

/**
 * GitHub のリリース1件(更新に要る分だけ)。
 */
final readonly class ReleaseInfo
{
    public function __construct(
        public Version $version,
        public string $name,
        public bool $prerelease,
        public string $body,
        public string $htmlUrl,
        public string $assetName,
        public string $assetUrl,
        /** GitHub が返す添付ファイルの SHA-256(16進数の小文字)。なければ null */
        public ?string $sha256,
        public ?int $size,
        public ?CarbonImmutable $publishedAt,
    ) {}
}
