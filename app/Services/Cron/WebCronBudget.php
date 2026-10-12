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

    /**
     * 時間の上限を延ばす(巡回のように、1ページに数十秒かかる処理のため)。PHP の実行時間の上限を延ばせたときだけ、
     * 締め切りも延ばす。延ばせないサーバーでは、何もしない(従来どおりの短い上限で区切る)。
     */
    public function extend(int $seconds): bool
    {
        if ($this->deadline === null) {
            return false;
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit($seconds + 30);
        }
        $limit = (int) ini_get('max_execution_time');
        if ($limit !== 0 && $limit < $seconds + 30) {
            return false;
        }

        $this->deadline = CarbonImmutable::now()->addSeconds($seconds);

        return true;
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
