<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 公開用の画像(WebP)の配信。public/storage のシンボリックリンクがない環境(レンタルサーバーなど)でも、
 * 同じ URL(/storage/media/…)で届ける。リンクがあるときは、Web サーバーが実ファイルを直接返すので、ここには来ない。
 * 配信するのは、公開用の3サイズの WebP だけ(元の画像は別のディスクにあり、この経路からは届かない)。
 */
final class MediaFileController extends Controller
{
    private const PATTERN = '#^media/\d{6}/[A-Za-z0-9]{40}-(?:400|800|1600)\.webp$#';

    public function show(string $path): StreamedResponse
    {
        abort_unless(preg_match(self::PATTERN, $path) === 1 && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
