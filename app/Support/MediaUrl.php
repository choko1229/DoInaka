<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * 公開用の画像(WebP の3サイズ)の URL。元の画像(非公開)の URL は作らない。
 */
final class MediaUrl
{
    public static function small(Media $media): ?string
    {
        return self::url($media, $media->path_small);
    }

    public static function medium(Media $media): ?string
    {
        return self::url($media, $media->path_medium);
    }

    public static function large(Media $media): ?string
    {
        return self::url($media, $media->path_large);
    }

    /** srcset(400w・800w・1600w) */
    public static function srcset(Media $media): string
    {
        $parts = [];
        foreach ([[self::small($media), 400], [self::medium($media), 800], [self::large($media), 1600]] as [$url, $w]) {
            if ($url !== null) {
                $parts[] = $url.' '.$w.'w';
            }
        }

        return implode(', ', $parts);
    }

    private static function url(Media $media, ?string $path): ?string
    {
        return $path === null || $path === '' ? null : Storage::disk($media->disk)->url($path);
    }
}
