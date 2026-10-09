<?php

declare(strict_types=1);

namespace App\Services\Web;

/**
 * ページの取得結果。notModified は ETag・Last-Modified が変わっていない(本文は読んでいない)。
 */
final readonly class FetchResult
{
    public const OK = 'ok';

    public const NOT_MODIFIED = 'not_modified';

    public const BLOCKED = 'blocked';

    public const FAILED = 'failed';

    public function __construct(
        public string $status,
        public string $html = '',
        public ?string $etag = null,
        public ?string $lastModified = null,
        public string $reason = '',
    ) {}

    public function ok(): bool
    {
        return $this->status === self::OK;
    }

    public function text(): string
    {
        return HtmlText::fromHtml($this->html);
    }

    public function hash(): string
    {
        return hash('sha256', $this->text());
    }
}
