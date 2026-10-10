<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * AI の判定を使えるか。使えないとき(キー未設定・設定でオフ・フェーズ5まで)は、投稿は人の審査に回る(設計書8・9章)。
 */
interface AiReviewGate
{
    public function available(): bool;
}
