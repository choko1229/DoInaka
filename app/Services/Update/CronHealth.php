<?php

declare(strict_types=1);

namespace App\Services\Update;

use App\Enums\AppMetaKey;
use App\Services\Setting\AppMetaService;
use Carbon\CarbonImmutable;

/**
 * cron(スケジューラ)が動いているか。動くたびに最終実行時刻を app_meta に書き、5分以上なければ止まったとみなす(設計書10.3)。
 */
class CronHealth
{
    public const STALE_MINUTES = 5;

    public function __construct(private readonly AppMetaService $meta) {}

    /**
     * @param  string  $source  'cli'(サーバーの cron が動かした)または 'web'(アクセスをきっかけに動かした)
     */
    public function beat(string $source = 'cli'): void
    {
        $now = now()->toIso8601String();
        $this->meta->set(AppMetaKey::SchedulerLastRun, $now);
        $this->meta->set($source === 'web' ? AppMetaKey::WebCronLastRun : AppMetaKey::SchedulerLastCliRun, $now);
    }

    /** 本物の cron(サーバーの cron)が最後に動いた時刻 */
    public function lastCliRun(): ?CarbonImmutable
    {
        $value = $this->meta->get(AppMetaKey::SchedulerLastCliRun);

        return $value === null ? null : CarbonImmutable::parse($value);
    }

    /** アクセスをきっかけに予約処理が最後に動いた時刻 */
    public function lastWebRun(): ?CarbonImmutable
    {
        $value = $this->meta->get(AppMetaKey::WebCronLastRun);

        return $value === null ? null : CarbonImmutable::parse($value);
    }

    /** 本物の cron が、いま動いているか(5分以内に動いた) */
    public function cliIsAlive(): bool
    {
        $last = $this->lastCliRun();

        return $last !== null && $last->diffInMinutes(now()) < self::STALE_MINUTES;
    }

    public function lastRun(): ?CarbonImmutable
    {
        $value = $this->meta->get(AppMetaKey::SchedulerLastRun);

        return $value === null ? null : CarbonImmutable::parse($value);
    }

    /** 一度も動いていない場合も「止まっている」 */
    public function isStale(): bool
    {
        $last = $this->lastRun();

        return $last === null || $last->diffInMinutes(now()) >= self::STALE_MINUTES;
    }
}
