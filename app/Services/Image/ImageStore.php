<?php

declare(strict_types=1);

namespace App\Services\Image;

use App\Enums\SettingKey;
use App\Models\Media;
use App\Models\MediaOriginal;
use App\Models\Submission;
use App\Models\User;
use App\Services\Setting\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 受け取ったままの元画像を、公開されない領域(local ディスク)に保存する(設計書12.1)。
 * ファイル名は利用者の名前を使わず、ランダムにする。公開側から元画像に届く経路は作らない。
 */
final class ImageStore
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @param  array{mime: string, extension: string, width: int, height: int}  $info  ImageValidator::validate の結果
     */
    public function storeOriginal(UploadedFile $file, array $info, ?Submission $submission, ?User $uploader, bool $rightsAgreed, int $sortOrder = 0, ?string $credit = null): Media
    {
        $path = 'originals/'.now()->format('Ym').'/'.Str::random(40).'.'.$info['extension'];
        Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));

        $media = new Media;
        $media->forceFill([
            'submission_id' => $submission?->id,
            'disk' => 'public',
            'width' => $info['width'],
            'height' => $info['height'],
            'credit' => $credit === null ? null : mb_substr($credit, 0, 200),
            'rights_agreed_at' => $rightsAgreed ? now() : null,
            'uploader_user_id' => $uploader?->id,
            'sort_order' => $sortOrder,
        ])->save();

        MediaOriginal::query()->create([
            'media_id' => $media->id,
            'disk' => 'local',
            'path' => $path,
            'mime' => $info['mime'],
            'size' => $file->getSize(),
            'expires_at' => now()->addDays($this->settings->int(SettingKey::UploadOriginalRetentionDays)),
        ]);

        return $media;
    }
}
