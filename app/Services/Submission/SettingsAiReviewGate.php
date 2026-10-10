<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Contracts\AiReviewGate;
use App\Services\Ai\AiClient;

/**
 * AI の判定を使えるか: 設定でオンで、キーがあるとき(設計書8・9章)。制限エラーで止まっているときは、
 * 判定待ちに入れて翌日に回す(AiClient が止まりを知らせるので、ここでは見ない)。
 */
final class SettingsAiReviewGate implements AiReviewGate
{
    public function __construct(private readonly AiClient $ai) {}

    public function available(): bool
    {
        return $this->ai->isConfigured();
    }
}
