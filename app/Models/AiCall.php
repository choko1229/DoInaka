<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * AI の呼び出しログ(90日)。
 *
 * @property int $id
 * @property string $purpose
 * @property int $priority
 * @property string|null $model
 * @property string $status
 * @property int|null $request_tokens
 * @property int|null $response_tokens
 * @property int|null $latency_ms
 * @property string|null $error
 * @property int|null $submission_id
 * @property Carbon|null $created_at
 */
class AiCall extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
