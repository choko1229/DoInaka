<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * settings テーブルのキー一覧(設計書14章)。文字列のキーはここにだけ書く。
 *
 * 型・初期値・秘密かどうか(暗号化して保存する)・空(null)を許すかを持つ。
 */
enum SettingKey: string
{
    // サイト
    case SiteName = 'site.name';
    case SiteDescription = 'site.description';
    case SiteOperator = 'site.operator';
    case SiteShareHost = 'site.share_host';

    // 外部サービス
    case GoogleClientId = 'google.client_id';
    case GoogleClientSecret = 'google.client_secret';
    case TurnstileSiteKey = 'turnstile.site_key';
    case TurnstileSecretKey = 'turnstile.secret_key';

    // AI
    case AiEnabled = 'ai.enabled';
    case AiApiKey = 'ai.api_key';
    case AiModelsReviewText = 'ai.models.review_text';
    case AiModelsReviewImage = 'ai.models.review_image';
    case AiModelsDraft = 'ai.models.draft';
    case AiModelsSuggest = 'ai.models.suggest';
    case AiModelsTip = 'ai.models.tip';
    case AiModelsCrawl = 'ai.models.crawl';
    case AiModelsRegionIntro = 'ai.models.region_intro';
    case AiModelsFactCheck = 'ai.models.fact_check';
    case AiModelsTakedownCheck = 'ai.models.takedown_check';
    case AiDailyLimit = 'ai.daily_limit';
    case AiTimeoutSec = 'ai.timeout_sec';

    // 審査
    case ReviewAutoApproveMinApproved = 'review.auto_approve_min_approved';
    case ReviewAutoApproveMinScore = 'review.auto_approve_min_score';
    case ReviewAutoRejectMaxScore = 'review.auto_reject_max_score';
    case ReviewRejectedRetentionDays = 'review.rejected_retention_days';
    case ReviewAutoRejectShadowDays = 'review.auto_reject_shadow_days';
    case ReviewImageFallbackMinScore = 'review.image_fallback_min_score';

    // 投稿・画像・スパム・プライバシー
    case UploadMaxMb = 'upload.max_mb';
    case UploadMaxFilesArticle = 'upload.max_files_article';
    case UploadMaxFilesOther = 'upload.max_files_other';
    case UploadOriginalRetentionDays = 'upload.original_retention_days';
    case SpamPostPerHour = 'spam.post_per_hour';
    case SpamMaxUrls = 'spam.max_urls';
    case PrivacyIpHashRetentionDays = 'privacy.ip_hash_retention_days';
    case TipOriginalRetentionDays = 'tip.original_retention_days';

    // 人気・検索・SEO
    case PopularityWeights = 'popularity.weights';
    case PopularityWindowDays = 'popularity.window_days';
    case SearchDriver = 'search.driver';
    case SeoIndexMinItems = 'seo.index_min_items';

    // 管理者
    case AdminTotpTtlHours = 'admin.totp_ttl_hours';
    case AdminRememberDeviceDays = 'admin.remember_device_days';

    // 広告・解析
    case AdsAdsenseClientId = 'ads.adsense_client_id';
    case AdsEnabled = 'ads.enabled';
    case AnalyticsGa4Id = 'analytics.ga4_id';

    // ログ
    case LogsAuditRetentionDays = 'logs.audit_retention_days';
    case LogsAiRetentionDays = 'logs.ai_retention_days';

    // 更新
    case UpdateRepository = 'update.repository';
    case UpdateAuto = 'update.auto';
    case UpdateWindowMode = 'update.window_mode';
    case UpdateFixedHour = 'update.fixed_hour';
    case UpdateAcceptBeta = 'update.accept_beta';

    // 通知・巡回
    case NotifyDiscordWebhookUrl = 'notify.discord_webhook_url';
    case CrawlMaxPagesPerSite = 'crawl.max_pages_per_site';
    case CrawlMinIntervalSeconds = 'crawl.min_interval_seconds';

    // お問い合わせ・コメント・削除依頼
    case ContactRetentionDays = 'contact.retention_days';
    case CommentAutoHideReports = 'comment.auto_hide_reports';
    case TakedownDailyLimitPerIp = 'takedown.daily_limit_per_ip';
    case TakedownTargetDays = 'takedown.target_days';
    case TakedownObjectionDays = 'takedown.objection_days';

    // 海外からのアクセス制限
    case GeoBlockOverseas = 'geo.block_overseas';
    case GeoAllowAdminAbroad = 'geo.allow_admin_abroad';

    // メール
    case MailFromAddress = 'mail.from_address';
    case MailSmtpHost = 'mail.smtp_host';
    case MailSmtpPort = 'mail.smtp_port';
    case MailSmtpUsername = 'mail.smtp_username';
    case MailSmtpPassword = 'mail.smtp_password';

    // イラスト(場所の対応表。分類・地域のスラッグ → field / island / mountain / village)
    case IllustPlaceMap = 'illust.place_map';

    public function type(): SettingType
    {
        return match ($this) {
            self::AiEnabled, self::AdsEnabled, self::UpdateAuto, self::UpdateAcceptBeta,
            self::GeoBlockOverseas, self::GeoAllowAdminAbroad => SettingType::Bool,

            self::AiModelsReviewText, self::AiModelsReviewImage, self::AiModelsDraft, self::AiModelsSuggest,
            self::AiModelsTip, self::AiModelsCrawl, self::AiModelsRegionIntro, self::AiModelsFactCheck,
            self::AiModelsTakedownCheck, self::PopularityWeights, self::IllustPlaceMap => SettingType::Json,

            self::ReviewAutoApproveMinScore, self::ReviewAutoRejectMaxScore,
            self::ReviewImageFallbackMinScore => SettingType::Float,

            self::AiDailyLimit, self::AiTimeoutSec, self::ReviewAutoApproveMinApproved,
            self::ReviewRejectedRetentionDays, self::ReviewAutoRejectShadowDays, self::UploadMaxMb,
            self::UploadMaxFilesArticle, self::UploadMaxFilesOther, self::UploadOriginalRetentionDays,
            self::SpamPostPerHour, self::SpamMaxUrls, self::PrivacyIpHashRetentionDays,
            self::TipOriginalRetentionDays, self::PopularityWindowDays, self::SeoIndexMinItems,
            self::AdminTotpTtlHours, self::AdminRememberDeviceDays, self::LogsAuditRetentionDays,
            self::LogsAiRetentionDays, self::UpdateFixedHour, self::CrawlMaxPagesPerSite,
            self::CrawlMinIntervalSeconds, self::ContactRetentionDays, self::CommentAutoHideReports,
            self::TakedownDailyLimitPerIp, self::TakedownTargetDays, self::TakedownObjectionDays,
            self::MailSmtpPort => SettingType::Int,

            default => SettingType::String,
        };
    }

    /**
     * 秘密の値は暗号化して保存し、ログと監査記録にはマスクして出す。
     */
    public function isSecret(): bool
    {
        return match ($this) {
            self::GoogleClientSecret, self::TurnstileSecretKey, self::AiApiKey,
            self::NotifyDiscordWebhookUrl, self::MailSmtpPassword => true,
            default => false,
        };
    }

    /**
     * 空(null)を保存できるか。ai.daily_limit は空なら上限なし。
     */
    public function isNullable(): bool
    {
        return $this === self::AiDailyLimit;
    }

    public function default(): mixed
    {
        return match ($this) {
            self::SiteName => 'ド田舎.net',
            self::SiteShareHost => 'do-inaka.net',
            self::AiEnabled => true,
            self::AiDailyLimit => null,
            self::AiTimeoutSec => 30,
            self::ReviewAutoApproveMinApproved => 5,
            self::ReviewAutoApproveMinScore => 0.9,
            self::ReviewAutoRejectMaxScore => 0.05,
            self::ReviewRejectedRetentionDays => 90,
            self::ReviewAutoRejectShadowDays => 14,
            self::ReviewImageFallbackMinScore => 0.95,
            self::UploadMaxMb => 10,
            self::UploadMaxFilesArticle => 10,
            self::UploadMaxFilesOther => 5,
            self::UploadOriginalRetentionDays => 60,
            self::SpamPostPerHour => 5,
            self::SpamMaxUrls => 3,
            self::PrivacyIpHashRetentionDays => 90,
            self::TipOriginalRetentionDays => 60,
            self::PopularityWeights => ['view' => 1, 'favorite' => 5, 'visited' => 3],
            self::PopularityWindowDays => 30,
            self::SearchDriver => 'ngram',
            self::SeoIndexMinItems => 5,
            self::AdminTotpTtlHours => 12,
            self::AdminRememberDeviceDays => 45,
            self::AdsEnabled => false,
            self::LogsAuditRetentionDays => 365,
            self::LogsAiRetentionDays => 90,
            self::UpdateRepository => 'choko1229/doinaka',
            self::UpdateAuto => true,
            self::UpdateWindowMode => 'auto',
            self::UpdateFixedHour => 4,
            self::UpdateAcceptBeta => false,
            self::CrawlMaxPagesPerSite => 30,
            self::CrawlMinIntervalSeconds => 10,
            self::ContactRetentionDays => 1095,
            self::CommentAutoHideReports => 3,
            self::TakedownDailyLimitPerIp => 3,
            self::TakedownTargetDays => 7,
            self::TakedownObjectionDays => 7,
            self::GeoBlockOverseas => true,
            self::GeoAllowAdminAbroad => false,
            self::MailFromAddress => 'contact@do-inaka.net',
            self::MailSmtpPort => 587,
            self::AiModelsReviewText, self::AiModelsReviewImage, self::AiModelsDraft, self::AiModelsSuggest,
            self::AiModelsTip, self::AiModelsCrawl, self::AiModelsRegionIntro, self::AiModelsFactCheck,
            self::AiModelsTakedownCheck => [],
            self::IllustPlaceMap => [
                'island' => ['island', 'sea', 'coast', 'port'],
                'mountain' => ['mountain', 'forest', 'onsen'],
                'village' => ['village', 'town', 'shrine', 'temple', 'festival'],
                'field' => ['field', 'farm', 'rice', 'pond'],
            ],
            default => '',
        };
    }

    /**
     * 値の中身の検証(型が合ったあとに呼ぶ)。通れば null、通らなければ理由を返す。
     */
    public function validate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::SearchDriver => in_array($value, ['ngram', 'like'], true) ? null : self::message('search_driver'),
            self::UpdateWindowMode => in_array($value, ['auto', 'fixed'], true) ? null : self::message('window_mode'),
            self::UpdateFixedHour => is_int($value) && $value >= 0 && $value <= 23 ? null : self::message('hour'),
            self::ReviewAutoApproveMinScore, self::ReviewAutoRejectMaxScore,
            self::ReviewImageFallbackMinScore => is_numeric($value) && $value >= 0 && $value <= 1 ? null : self::message('score'),
            self::AiTimeoutSec, self::UploadMaxMb, self::SpamPostPerHour, self::SpamMaxUrls, self::PopularityWindowDays,
            self::AdminTotpTtlHours, self::AdminRememberDeviceDays => is_int($value) && $value >= 1 ? null : self::message('positive_int'),
            self::AiDailyLimit => is_int($value) && $value >= 1 ? null : self::message('positive_int_or_null'),
            default => null,
        };
    }

    private static function message(string $name): string
    {
        $text = __('settings.errors.'.$name);

        return is_string($text) ? $text : $name;
    }
}
