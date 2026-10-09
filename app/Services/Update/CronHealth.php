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

    public function beat(): void
    {
        $this->meta->set(AppMetaKey::SchedulerLastRun, now()->toIso8601String());
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
