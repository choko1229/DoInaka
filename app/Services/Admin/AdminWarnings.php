<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\CronMode;
use App\Services\Cron\WebCronStatus;
use App\Services\Setting\Prelaunch;
use App\Services\Update\CronHealth;
use App\Services\Update\CronWatcher;

/**
 * 管理画面の上部に出す運営上の警告(設計書10.3・13.3)。
 */
class AdminWarnings
{
    public function __construct(private readonly CronHealth $cron, private readonly Prelaunch $prelaunch, private readonly WebCronStatus $web) {}

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

        // アクセスで動かしているとき(本物の cron はない)は、間があくのがふつう。自分自身を呼べない状態が続いたときだけ警告する
        if ($this->web->mode() === CronMode::Web) {
            if ($this->web->failures() >= CronWatcher::WEB_FAILURES) {
                $warnings[] = ['title' => __('admin.warning_webcron_title'), 'body' => __('admin.warning_webcron_body')];
            }
        } elseif ($this->cron->isStale()) {
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
