<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RightType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 削除依頼を受けて、確認が終わるまでぼかして「確認中」と出している対象。released_at が入ったら終わり。
 *
 * @property int $id
 * @property int $inquiry_id
 * @property string $holdable_type
 * @property int $holdable_id
 * @property int|null $media_id
 * @property RightType $right_type
 * @property bool $reveal_allowed
 * @property Carbon|null $released_at
 */
class ContentHold extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['right_type' => RightType::class, 'reveal_allowed' => 'boolean', 'released_at' => 'datetime'];
    }

    /** @return BelongsTo<Inquiry, $this> */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }
}
