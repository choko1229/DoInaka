<?php

declare(strict_types=1);

namespace App\Services\Setting;

use App\Enums\AiPurpose;
use App\Enums\SettingKey;

/**
 * 設定画面(AdminSettings。設計書14章)のタブと、タブごとの設定。
 * 更新(update.*)は「アップデート」の画面にあるので、ここには含めない。
 */
final class SettingsCatalog
{
    /** @return array<string, list<SettingKey>> タブの名前 => 設定(画面の順) */
    public static function tabs(): array
    {
        return [
            'site' => [SettingKey::SiteName, SettingKey::SiteDescription, SettingKey::SiteOperator, SettingKey::SiteShareHost, SettingKey::SitePrelaunch],
            'login' => [SettingKey::GoogleClientId, SettingKey::GoogleClientSecret, SettingKey::AdminTotpTtlHours, SettingKey::AdminRememberDeviceDays],
            'ai' => [
                SettingKey::AiEnabled, SettingKey::AiApiKey, SettingKey::AiTimeoutSec, SettingKey::AiDailyLimit,
                SettingKey::ReviewAutoApproveMinApproved, SettingKey::ReviewAutoApproveMinScore, SettingKey::ReviewAutoRejectMaxScore,
                SettingKey::ReviewAutoRejectShadowDays, SettingKey::ReviewImageFallbackMinScore, SettingKey::ReviewRejectedRetentionDays,
            ],
            'posts' => [
                SettingKey::TurnstileSiteKey, SettingKey::TurnstileSecretKey, SettingKey::SpamPostPerHour, SettingKey::SpamMaxUrls,
                SettingKey::UploadMaxMb, SettingKey::UploadMaxFilesArticle, SettingKey::UploadMaxFilesOther, SettingKey::UploadOriginalRetentionDays,
                SettingKey::TipOriginalRetentionDays, SettingKey::PrivacyIpHashRetentionDays, SettingKey::CommentAutoHideReports,
            ],
            'search' => [SettingKey::SearchDriver, SettingKey::SeoIndexMinItems, SettingKey::PopularityWindowDays, SettingKey::PopularityWeights],
            'ads' => [SettingKey::AdsEnabled, SettingKey::AdsAdsenseClientId, SettingKey::AnalyticsGa4Id],
            'notify' => [SettingKey::NotifyDiscordWebhookUrl, SettingKey::CrawlMaxPagesPerSite, SettingKey::CrawlMinIntervalSeconds],
            'contact' => [SettingKey::ContactRetentionDays, SettingKey::TakedownDailyLimitPerIp, SettingKey::TakedownTargetDays, SettingKey::TakedownObjectionDays],
            'geo' => [SettingKey::GeoBlockOverseas, SettingKey::GeoAllowAdminAbroad],
            'mail' => [SettingKey::MailFromAddress, SettingKey::MailSmtpHost, SettingKey::MailSmtpPort, SettingKey::MailSmtpUsername, SettingKey::MailSmtpPassword],
            'logs' => [SettingKey::LogsAuditRetentionDays, SettingKey::LogsAiRetentionDays],
        ];
    }

    /**
     * AI のタブにある、用途ごとのモデルの設定(第1候補と予備)。
     *
     * @return array<string, SettingKey>
     */
    public static function modelKeys(): array
    {
        $keys = [];
        foreach (AiPurpose::cases() as $purpose) {
            $keys[$purpose->value] = $purpose->settingKey();
        }

        return $keys;
    }

    /** @return list<SettingKey> 選択肢で選ぶ設定 */
    public static function selectKeys(): array
    {
        return [SettingKey::SearchDriver];
    }
}
