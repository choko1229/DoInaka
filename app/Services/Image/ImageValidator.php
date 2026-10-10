<?php

declare(strict_types=1);

namespace App\Services\Image;

use App\Enums\SettingKey;
use App\Exceptions\UploadRejected;
use App\Services\Setting\SettingsService;
use Illuminate\Http\UploadedFile;
use Imagick;
use Throwable;

/**
 * アップロードされた画像の受付時の検証(設計書12.1)。
 * 拡張子や申告された MIME ではなく、ファイルの中身で形式を判定し、画像として読めて、大きすぎないことを確かめる。
 */
final class ImageValidator
{
    /** 展開後に大きくなりすぎる写真は断る(4800万画素は展開で約372MB。設計書12.3) */
    public const MAX_PIXELS = 40_000_000;

    /** @var array<string, string> 中身の MIME → 保存に使う拡張子 */
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/heic' => 'heic'];

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array{mime: string, extension: string, width: int, height: int}
     *
     * @throws UploadRejected
     */
    public function validate(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new UploadRejected(__('submission.image_upload_failed', ['name' => $this->name($file)]));
        }

        $maxBytes = $this->settings->int(SettingKey::UploadMaxMb) * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            throw new UploadRejected(__('submission.image_too_big', ['name' => $this->name($file), 'mb' => $this->settings->int(SettingKey::UploadMaxMb)]));
        }

        $path = $file->getRealPath();
        if ($path === false) {
            throw new UploadRejected(__('submission.image_upload_failed', ['name' => $this->name($file)]));
        }

        $mime = $this->detect($path);
        if ($mime === null) {
            throw new UploadRejected(__('submission.image_bad_type', ['name' => $this->name($file)]));
        }

        [$width, $height] = $this->dimensions($path, $file);
        if ($width * $height > self::MAX_PIXELS) {
            throw new UploadRejected(__('submission.image_too_large_pixels', ['name' => $this->name($file)]));
        }

        return ['mime' => $mime, 'extension' => self::TYPES[$mime], 'width' => $width, 'height' => $height];
    }

    /** ファイルの先頭の中身で形式を判定する(JPEG / PNG / WebP / HEIC)。それ以外は null */
    public function detect(string $path): ?string
    {
        $head = (string) file_get_contents($path, false, null, 0, 32);
        if (strlen($head) < 12) {
            return null;
        }

        if (str_starts_with($head, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($head, "\x89PNG\r\n\x1A\n")) {
            return 'image/png';
        }
        if (str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP') {
            return 'image/webp';
        }
        // HEIC / HEIF は ISO base media の ftyp ブランドで見分ける
        if (substr($head, 4, 4) === 'ftyp' && in_array(substr($head, 8, 4), ['heic', 'heix', 'hevc', 'hevx', 'heim', 'heis', 'hevm', 'hevs', 'mif1', 'msf1'], true)) {
            return 'image/heic';
        }

        return null;
    }

    /**
     * 画像として読めるかを、Imagick で調べる(中身を展開せず、大きさだけ読む)。
     *
     * @return array{int, int}
     *
     * @throws UploadRejected
     */
    private function dimensions(string $path, UploadedFile $file): array
    {
        try {
            $image = new Imagick;
            $image->pingImage($path);
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            $image->clear();
        } catch (Throwable) {
            throw new UploadRejected(__('submission.image_unreadable', ['name' => $this->name($file)]));
        }

        if ($width < 1 || $height < 1) {
            throw new UploadRejected(__('submission.image_unreadable', ['name' => $this->name($file)]));
        }

        return [$width, $height];
    }

    /** 表示用のファイル名(利用者が付けた名前は、長さだけ切って出す。エスケープは画面側) */
    private function name(UploadedFile $file): string
    {
        return mb_substr($file->getClientOriginalName(), 0, 60);
    }
}
