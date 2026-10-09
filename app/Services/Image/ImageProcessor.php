<?php

declare(strict_types=1);

namespace App\Services\Image;

use App\Exceptions\UploadRejected;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use Throwable;

/**
 * 公開用の画像を作る(設計書12.1): 向きを直し、位置情報を含むメタデータをすべて落とし、
 * 長辺 1600・800・400px の WebP(品質80)にする。元が小さいときは拡大しない。
 */
final class ImageProcessor
{
    /** @var array<string, int> 列名 → 長辺 */
    public const SIZES = ['path_large' => 1600, 'path_medium' => 800, 'path_small' => 400];

    /** ImageMagick に使わせるメモリ(超えた分はディスクに逃がす) */
    private const MEMORY_LIMIT = 256 * 1024 * 1024;

    /** 元画像(非公開領域)から公開用を作る */
    public function process(Media $media): Media
    {
        $original = $media->original;
        if ($original === null) {
            throw new UploadRejected(__('submission.image_missing_original'));
        }

        return $this->generate(Storage::disk($original->disk)->path($original->path), $media);
    }

    /** 指定したファイル(管理者が個人情報を隠した画像など)から公開用を作る */
    public function generate(string $sourcePath, Media $media): Media
    {
        $variants = $this->render($sourcePath);
        $directory = 'media/'.now()->format('Ym');
        $this->protectDirectory($directory);

        $name = Str::random(40);
        $paths = [];
        $width = null;
        $height = null;

        foreach (self::SIZES as $column => $size) {
            $file = $variants[$size];
            $path = "{$directory}/{$name}-{$size}.webp";
            Storage::disk('public')->put($path, $file['blob']);
            $paths[$column] = $path;
            if ($column === 'path_large') {
                $width = $file['width'];
                $height = $file['height'];
            }
        }

        // 作り直した場合は、前の公開用ファイルを消す
        foreach (array_keys(self::SIZES) as $column) {
            $old = $media->getAttribute($column);
            if (is_string($old) && $old !== '') {
                Storage::disk('public')->delete($old);
            }
        }

        $media->forceFill($paths + ['width' => $width, 'height' => $height])->save();

        return $media;
    }

    /**
     * @return array<int, array{blob: string, width: int, height: int}> 長辺ごとの WebP
     */
    public function render(string $sourcePath): array
    {
        try {
            Imagick::setResourceLimit(Imagick::RESOURCETYPE_MEMORY, self::MEMORY_LIMIT);
            Imagick::setResourceLimit(Imagick::RESOURCETYPE_MAP, self::MEMORY_LIMIT);

            // 複数フレーム(アニメーション・HEIC の複数画像)は最初の1枚だけ使う
            $image = new Imagick($sourcePath.'[0]');
            $this->orient($image);
            // メタデータ(EXIF・GPS・カラープロファイルを含むすべて)を落とす
            $image->stripImage();
            $image->setImageBackgroundColor('white');
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
        } catch (Throwable $e) {
            throw new UploadRejected(__('submission.image_process_failed'), 0, $e);
        }

        $variants = [];
        foreach (self::SIZES as $size) {
            $copy = clone $image;
            $w = $copy->getImageWidth();
            $h = $copy->getImageHeight();
            $longest = max($w, $h);

            if ($longest > $size) {
                $scale = $size / $longest;
                $copy->resizeImage(max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)), Imagick::FILTER_LANCZOS, 1);
            }

            $copy->setImageFormat('webp');
            $copy->setImageCompressionQuality(80);
            $copy->stripImage();

            $variants[$size] = ['blob' => $copy->getImageBlob(), 'width' => $copy->getImageWidth(), 'height' => $copy->getImageHeight()];
            $copy->clear();
        }
        $image->clear();

        return $variants;
    }

    /** EXIF の向きを反映して、向きの情報を初期値に戻す */
    private function orient(Imagick $image): void
    {
        switch ($image->getImageOrientation()) {
            case Imagick::ORIENTATION_TOPRIGHT:
                $image->flopImage();
                break;
            case Imagick::ORIENTATION_BOTTOMRIGHT:
                $image->rotateImage('white', 180);
                break;
            case Imagick::ORIENTATION_BOTTOMLEFT:
                $image->flipImage();
                break;
            case Imagick::ORIENTATION_LEFTTOP:
                $image->transposeImage();
                break;
            case Imagick::ORIENTATION_RIGHTTOP:
                $image->rotateImage('white', 90);
                break;
            case Imagick::ORIENTATION_RIGHTBOTTOM:
                $image->transverseImage();
                break;
            case Imagick::ORIENTATION_LEFTBOTTOM:
                $image->rotateImage('white', 270);
                break;
            default:
                break;
        }
        $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    }

    /** 公開ディレクトリで PHP が動かないようにする(画像しか置かないが、念のため) */
    private function protectDirectory(string $directory): void
    {
        $disk = Storage::disk('public');
        $root = 'media/.htaccess';
        if (! $disk->exists($root)) {
            $disk->put($root, "# 画像だけを置く場所。スクリプトは動かさない\nphp_flag engine off\nOptions -ExecCGI\n<FilesMatch \"\\.(php[0-9]?|phtml|phar|cgi|pl)\$\">\n    Require all denied\n</FilesMatch>\n");
        }
        $disk->makeDirectory($directory);
    }
}
