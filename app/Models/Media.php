<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * 公開用の画像(WebP の3サイズ)。
 *
 * @property int $id
 * @property int|null $submission_id
 * @property string|null $mediable_type
 * @property int|null $mediable_id
 * @property string $disk
 * @property string|null $path_large
 * @property string|null $path_medium
 * @property string|null $path_small
 * @property int|null $width
 * @property int|null $height
 * @property string|null $alt
 * @property string|null $credit
 * @property Carbon|null $rights_agreed_at
 * @property int|null $uploader_user_id
 * @property int $sort_order
 */
class Media extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['rights_agreed_at' => 'datetime', 'ai_result' => 'array'];
    }

    /** @return HasOne<MediaOriginal, $this> */
    public function original(): HasOne
    {
        return $this->hasOne(MediaOriginal::class);
    }

    /** 公開用の画像ができているか */
    public function isProcessed(): bool
    {
        return $this->path_large !== null;
    }
}
