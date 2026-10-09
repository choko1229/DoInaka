<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * 公開ページの <head> に出す情報(タイトル・説明・正規 URL・noindex・OGP・構造化データ・パンくず)。設計書15章。
 */
final class PageMeta
{
    /**
     * @param  list<array{name: string, url: string|null}>  $breadcrumbs  最後の要素は url なし(いまのページ)
     * @param  list<array<string, mixed>>  $jsonLd
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        /** 正規 URL(完全な URL)。出さないページは null */
        public ?string $canonical = null,
        public bool $noindex = false,
        public ?string $image = null,
        public string $ogType = 'website',
        public array $breadcrumbs = [],
        public array $jsonLd = [],
    ) {
        $this->description = $description === null ? null : Str::limit(trim((string) preg_replace('/\s+/u', ' ', $description)), 120, '…');
    }
}
