<?php

declare(strict_types=1);

namespace App\Services\Update;

use App\Contracts\ReleaseDownloader;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class HttpReleaseDownloader implements ReleaseDownloader
{
    public function download(string $url, string $destination): void
    {
        // 配布元は GitHub の https だけ(リダイレクト先は GitHub の配信ホスト)
        if (! str_starts_with($url, 'https://github.com/')) {
            throw new RuntimeException(__('update.bad_download_url'));
        }

        try {
            $response = Http::withUserAgent('DoInaka-Updater')
                ->timeout(config()->integer('update.download_timeout'))
                ->sink($destination)
                ->get($url);
        } catch (Throwable $e) {
            @unlink($destination);

            throw new RuntimeException(__('update.download_failed', ['reason' => $e->getMessage()]), 0, $e);
        }

        if (! $response->successful()) {
            @unlink($destination);

            throw new RuntimeException(__('update.download_failed', ['reason' => 'HTTP '.$response->status()]));
        }
    }
}
