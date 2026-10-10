<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReplyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * お問い合わせへの返信・結果のお知らせ(メール)。
 *
 * @property int $id
 * @property int $inquiry_id
 * @property string $kind
 * @property string $body
 * @property ReplyStatus $status
 * @property string|null $failure
 * @property Carbon|null $sent_at
 */
class InquiryReply extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => ReplyStatus::class, 'sent_at' => 'datetime'];
    }
}
