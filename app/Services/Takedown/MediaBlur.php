<?php

declare(strict_types=1);

namespace App\Services\Takedown;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Imagick;

/**
 * 削除依頼の確認中、写真をぼかす。公開ディレクトリの3サイズの WebP を、ぼかしたものに差し替える
 * (HTML ではなく実ファイルをぼかすので、画像の URL を直接開いても元の写真は出ない)。
 * 元の WebP は非公開のディスクに退避し、残すと決まったら戻す。
 */
final class MediaBlur
{
    private const BACKUP_DISK = 'local';

    /** @return list<string> 公開側のパス */
    private function paths(Media $media): array
    {
        return array_values(array_filter([$media->path_small, $media->path_medium, $media->path_large], fn (?string $p): bool => $p !== null && $p !== ''));
    }

    private function backup(Media $media, string $path): string
    {
        return 'held/'.$media->id.'/'.basename($path);
    }

    public function isBlurred(Media $media): bool
    {
        foreach ($this->paths($media) as $path) {
            return Storage::disk(self::BACKUP_DISK)->exists($this->backup($media, $path));
        }

        return false;
    }

    public function hide(Media $media): void
    {
        if ($this->isBlurred($media)) {
            return;
        }

        $public = Storage::disk($media->disk);
        foreach ($this->paths($media) as $path) {
            if (! $public->exists($path)) {
                continue;
            }
            $original = (string) $public->get($path);
            Storage::disk(self::BACKUP_DISK)->put($this->backup($media, $path), $original);
            $public->put($path, $this->blur($original));
        }
    }

    /** ぼかしを外して、元の写真を戻す */
    public function restore(Media $media): void
    {
        $public = Storage::disk($media->disk);
        foreach ($this->paths($media) as $path) {
            $backup = $this->backup($media, $path);
            if (Storage::disk(self::BACKUP_DISK)->exists($backup)) {
                $public->put($path, (string) Storage::disk(self::BACKUP_DISK)->get($backup));
            }
        }
        $this->purge($media);
    }

    /** 退避した元の写真を消す(削除するとき、戻したあと) */
    public function purge(Media $media): void
    {
        Storage::disk(self::BACKUP_DISK)->deleteDirectory('held/'.$media->id);
    }

    /** 見る人が「タップで表示」するための元の写真(中サイズ)。ぼかし中でなければ公開側のもの */
    public function revealPath(Media $media): ?string
    {
        $path = $media->path_medium ?? $media->path_large;
        if ($path === null) {
            return null;
        }

        return Storage::disk(self::BACKUP_DISK)->exists($this->backup($media, $path)) ? $this->backup($media, $path) : null;
    }

    /** 縮小して引き伸ばし(粗くして)、さらにぼかす。元の形が分からない程度に */
    private function blur(string $webp): string
    {
        $image = new Imagick;
        $image->readImageBlob($webp);
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        $image->resizeImage(max(1, intdiv($width, 24)), max(1, intdiv($height, 24)), Imagick::FILTER_TRIANGLE, 1);
        $image->resizeImage($width, $height, Imagick::FILTER_GAUSSIAN, 1);
        $image->blurImage(0, 12);
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(50);
        $blob = $image->getImageBlob();
        $image->clear();

        return $blob;
    }
}
