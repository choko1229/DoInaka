<?php

declare(strict_types=1);

namespace App\Services\Setting;

use App\Enums\SettingKey;
use Throwable;

/**
 * 公開前モード(settings の site.prelaunch)。オンの間は、管理者・編集者以外には「準備中」を返し、検索エンジンにも載せない。
 * 設定を読めないとき(設置前など)は、オフとして扱う。
 */
final class Prelaunch
{
    public function __construct(private readonly SettingsService $settings) {}

    public function isOn(): bool
    {
        try {
            return $this->settings->bool(SettingKey::SitePrelaunch);
        } catch (Throwable) {
            return false;
        }
    }
}
