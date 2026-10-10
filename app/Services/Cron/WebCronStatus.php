<?php

declare(strict_types=1);

namespace App\Services\Cron;

use App\Enums\AppMetaKey;
use App\Enums\CronMode;
use App\Enums\SettingKey;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use App\Services\Update\CronHealth;
use Carbon\CarbonImmutable;

/**
 * 予約処理の動かし方(サーバーの cron / アクセスで動かす)と、最後の実行の様子。管理画面とダッシュボードに出す。
 * 本物の cron が動いているとわかったら、アクセスで動かす方式は、自動で止まる(mode が Cron になる)。
 */
final class WebCronStatus
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly CronHealth $health,
        private readonly AppMetaService $meta,
    ) {}

    public function enabled(): bool
    {
        return $this->settings->bool(SettingKey::CronWebEnabled);
    }

    public function mode(): CronMode
    {
        if ($this->health->cliIsAlive()) {
            return CronMode::Cron;
        }

        return $this->enabled() ? CronMode::Web : CronMode::Off;
    }

    public function lastRun(): ?CarbonImmutable
    {
        return $this->health->lastRun();
    }

    public function lastWebRun(): ?CarbonImmutable
    {
        return $this->health->lastWebRun();
    }

    /**
     * 最後のアクセスで動かした実行の結果(動かした処理・時間・失敗)。
     *
     * @return array<string, mixed>
     */
    public function lastResult(): array
    {
        $json = $this->meta->get(AppMetaKey::WebCronLastResult);
        $data = $json === null ? null : json_decode($json, true);

        /** @var array<string, mixed> $result */
        $result = is_array($data) ? $data : [];

        return $result;
    }

    /** 自分自身を呼べなかった回数(続けて) */
    public function failures(): int
    {
        return (int) ($this->meta->get(AppMetaKey::WebCronFailures) ?? 0);
    }
}
