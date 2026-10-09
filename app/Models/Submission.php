<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubmissionAction;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 投稿(設計書3.4・8章)。status は SubmissionStateMachine だけが書き換える。
 *
 * @property int $id
 * @property string $receipt_no
 * @property SubmissionType $type
 * @property SubmissionAction $action
 * @property string|null $target_type
 * @property int|null $target_id
 * @property array<string, mixed>|null $payload
 * @property int|null $user_id
 * @property string|null $ip_hash
 * @property SubmissionStatus $status
 * @property string|null $ai_status
 * @property float|null $ai_score
 * @property array<string, mixed>|null $ai_result
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $reject_reason
 * @property string|null $auto_decision
 * @property Carbon|null $consented_at
 * @property string|null $terms_version
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 */
class Submission extends Model
{
    protected $guarded = ['id', 'status'];

    protected function casts(): array
    {
        return [
            'type' => SubmissionType::class,
            'action' => SubmissionAction::class,
            'status' => SubmissionStatus::class,
            'payload' => 'array',
            'ai_result' => 'array',
            'ai_score' => 'float',
            'reviewed_at' => 'datetime',
            'consented_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return HasMany<Media, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<Correction, $this> */
    public function corrections(): HasMany
    {
        return $this->hasMany(Correction::class);
    }

    /** payload から整数を取り出す(なければ 0) */
    public function number(string $key): int
    {
        $value = $this->payload[$key] ?? null;

        return is_numeric($value) ? (int) $value : 0;
    }

    /** payload から文字列を取り出す(型の保証つき) */
    public function text(string $key): ?string
    {
        $value = $this->payload[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
