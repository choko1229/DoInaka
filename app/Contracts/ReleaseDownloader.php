<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * リリースZIPのダウンロード。テストでは差し替える。
 */
interface ReleaseDownloader
{
    /**
     * @throws \RuntimeException ダウンロードに失敗したとき
     */
    public function download(string $url, string $destination): void;
}
