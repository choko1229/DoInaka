<?php

declare(strict_types=1);

namespace App\Services\Update;

use App\Contracts\Notifier;
use App\Enums\AppMetaKey;
use App\Enums\SettingKey;
use App\Enums\UpdateTrigger;
use App\Models\UpdateRun;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;

/**
 * 定期処理(毎日、更新の時間帯のはじめ)と管理画面から使う、更新の入口。
 *
 * - 自動更新が ON なら、確認と同時に適用する
 * - OFF なら、新しい版を Discord に1度だけ知らせて、適用はしない(管理画面の「今すぐ更新」で適用する)
 */
class AutoUpdater
{
    public function __construct(
        private readonly UpdateChecker $checker,
        private readonly UpdateApplier $applier,
        private readonly SettingsService $settings,
        private readonly AppMetaService $meta,
        private readonly Notifier $notifier,
    ) {}

    /**
     * 定期処理から呼ぶ。更新を適用したときだけ UpdateRun を返す。
     */
    public function runScheduled(): ?UpdateRun
    {
        $result = $this->checker->check();
        if ($result->available === null) {
            return null;
        }

        if (! $this->settings->bool(SettingKey::UpdateAuto)) {
            $this->notifyAvailableOnce($result->available);

            return null;
        }

        return $this->applier->apply($result->available, UpdateTrigger::Auto);
    }

    /**
     * 管理画面の「今すぐ更新」。確認し直してから適用する。新しい版がなければ null。
     */
    public function runManual(string $triggeredBy): ?UpdateRun
    {
        $result = $this->checker->check();

        return $result->available === null ? null : $this->applier->apply($result->available, UpdateTrigger::Manual, $triggeredBy);
    }

    private function notifyAvailableOnce(ReleaseInfo $release): void
    {
        $version = $release->version->__toString();
        if ($this->meta->get(AppMetaKey::LastNotifiedVersion) === $version) {
            return;
        }

        $this->notifier->send(__('update.notify_available', ['version' => $version]));
        $this->meta->set(AppMetaKey::LastNotifiedVersion, $version);
    }
}
