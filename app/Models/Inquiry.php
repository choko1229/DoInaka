<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InquiryKind;
use App\Enums\InquiryStatus;
use App\Enums\RightType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * お問い合わせ。メールアドレスは管理者だけが見る。
 *
 * @property int $id
 * @property string $receipt_no
 * @property InquiryKind $kind
 * @property string|null $target_url
 * @property string|null $target_type
 * @property int|null $target_id
 * @property int|null $media_id
 * @property RightType|null $right_type
 * @property string|null $organizer_name
 * @property string $body
 * @property string|null $email
 * @property bool $urgent
 * @property array<string, mixed>|null $ai_check
 * @property InquiryStatus $status
 * @property string|null $result
 * @property Carbon|null $created_at
 */
class Inquiry extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'kind' => InquiryKind::class,
            'right_type' => RightType::class,
            'status' => InquiryStatus::class,
            'urgent' => 'boolean',
            'ai_check' => 'array',
            'handled_at' => 'datetime',
            'consented_at' => 'datetime',
        ];
    }

    /** @return HasMany<InquiryReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(InquiryReply::class);
    }

    /** @return HasMany<ContentHold, $this> */
    public function holds(): HasMany
    {
        return $this->hasMany(ContentHold::class);
    }

    /** @return HasOne<TakedownConsent, $this> */
    public function consent(): HasOne
    {
        return $this->hasOne(TakedownConsent::class);
    }
}
