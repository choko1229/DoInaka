<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 投稿した会員への、削除に同意するかの照会。依頼した人の情報は持たない。
 *
 * @property int $id
 * @property int $inquiry_id
 * @property int $user_id
 * @property ConsentStatus $status
 * @property string|null $objection_reason
 * @property Carbon $deadline_at
 * @property Carbon|null $responded_at
 */
class TakedownConsent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => ConsentStatus::class, 'deadline_at' => 'datetime', 'responded_at' => 'datetime'];
    }

    /** @return BelongsTo<Inquiry, $this> */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    /** 同意があった、または期限まで反対がなかった(=削除できる。実際に消すのは管理者) */
    public function allowsRemoval(): bool
    {
        return $this->status === ConsentStatus::Agreed || ($this->status === ConsentStatus::Pending && $this->deadline_at->isPast());
    }
}
