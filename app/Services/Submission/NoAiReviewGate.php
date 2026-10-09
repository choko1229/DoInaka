<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Contracts\AiReviewGate;

/**
 * AI の判定をまだ持たない版(フェーズ5)。すべて人の審査に回す。フェーズ6で本物に差し替える。
 */
final class NoAiReviewGate implements AiReviewGate
{
    public function available(): bool
    {
        return false;
    }
}
