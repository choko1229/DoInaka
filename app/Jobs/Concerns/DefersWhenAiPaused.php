<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Services\Ai\AiUsage;
use Carbon\CarbonInterface;

/**
 * AI の制限エラーで止まっている間は、ジョブを失敗にせず、リセット(UTC 0時)のあとに回す(設計書9.3)。
 */
trait DefersWhenAiPaused
{
    /** 止まっていれば、リセットまで待つ秒数を返す(止まっていなければ null) */
    protected function secondsUntilAiResumes(): ?int
    {
        $until = app(AiUsage::class)->pausedUntil();

        return $until === null ? null : max(60, (int) now()->diffInSeconds($until, true) + 60);
    }

    protected function secondsUntilTime(CarbonInterface $at): int
    {
        return max(60, (int) now()->diffInSeconds($at, true) + 60);
    }
}
