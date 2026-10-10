<?php

declare(strict_types=1);

namespace App\Services\Cron;

use Carbon\CarbonImmutable;

/**
 * アクセスで動かした予約処理の、時間の上限(既定25秒)。長い処理(巡回など)は、これを見て区切り、次回に続きから再開する。
 * 本物の cron(CLI)で動いているときは、上限なし(active() が false)。
 */
final class WebCronBudget
{
    private ?CarbonImmutable $deadline = null;

    public function start(int $seconds): void
    {
        $this->deadline = CarbonImmutable::now()->addSeconds($seconds);
    }

    public function stop(): void
    {
        $this->deadline = null;
    }

    public function active(): bool
    {
        return $this->deadline !== null;
    }

    public function deadline(): ?CarbonImmutable
    {
        return $this->deadline;
    }

    /** 残り秒数(上限がなければ null) */
    public function remaining(): ?int
    {
        return $this->deadline === null ? null : max(0, (int) CarbonImmutable::now()->diffInSeconds($this->deadline, false));
    }
}
