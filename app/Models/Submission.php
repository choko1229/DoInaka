<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 投稿・情報提供・修正依頼など(審査前のデータ)。状態の遷移はフェーズ5の SubmissionStateMachine だけが行う。
 */
class Submission extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'ai_result' => 'array', 'reviewed_at' => 'datetime', 'consented_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
