<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Services\Setting\Prelaunch;
use App\Services\Update\CronHealth;

/**
 * 管理画面の上部に出す運営上の警告(設計書10.3・13.3)。
 */
class AdminWarnings
{
    public function __construct(private readonly CronHealth $cron, private readonly Prelaunch $prelaunch) {}

    /**
     * @return list<array{title: string, body: string}>
     */
    public function all(): array
    {
        $warnings = [];

        if ($this->prelaunch->isOn()) {
            $warnings[] = ['title' => __('prelaunch.badge'), 'body' => __('prelaunch.badge_help')];
        }

        if (config('app.debug') === true) {
            $warnings[] = ['title' => __('admin.warning_debug_title'), 'body' => __('admin.warning_debug_body')];
        }

        if ($this->cron->isStale()) {
            $last = $this->cron->lastRun();
            $warnings[] = [
                'title' => __('admin.warning_cron_title'),
                'body' => $last === null
                    ? __('admin.warning_cron_never')
                    : __('admin.warning_cron_body', ['minutes' => (int) $last->diffInMinutes(now())]),
            ];
        }

        return $warnings;
    }
}
