<?php

declare(strict_types=1);

namespace App\Services\Update;

use App\Contracts\Notifier;
use App\Enums\AppMetaKey;
use App\Services\Setting\AppMetaService;

/**
 * cron(スケジューラ)の停止を Discord に知らせる(設計書10.3)。cron が止まっていると自分では知らせられないので、
 * Web へのアクセスのついでに呼ばれる(WatchCron)。止まったときに1回、動き出したときに1回だけ通知する。
 * 状態は app_meta に持つので、同じ停止で何度も通知しない。
 */
final class CronWatcher
{
    public const STOPPED = 'stopped';

    public function __construct(
        private readonly CronHealth $health,
        private readonly AppMetaService $meta,
        private readonly Notifier $notifier,
    ) {}

    /** @return 'stopped'|'recovered'|null 通知した内容(通知しなかったら null) */
    public function check(): ?string
    {
        // 設置前は、まだ cron がない
        if (! $this->meta->isInstalled()) {
            return null;
        }

        $alerted = $this->meta->get(AppMetaKey::CronAlertState) === self::STOPPED;
        $stale = $this->health->isStale();

        if ($stale && ! $alerted) {
            $last = $this->health->lastRun();
            $this->notifier->send($last === null
                ? __('admin.cron_notice_never')
                : __('admin.cron_notice_stopped', ['minutes' => (int) $last->diffInMinutes(now())]));
            $this->meta->set(AppMetaKey::CronAlertState, self::STOPPED);

            return 'stopped';
        }

        if (! $stale && $alerted) {
            $this->notifier->send(__('admin.cron_notice_recovered'));
            $this->meta->set(AppMetaKey::CronAlertState, 'ok');

            return 'recovered';
        }

        return null;
    }
}
