<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * AI の用途(設計書9.2)。用途ごとに、使うモデルの設定・優先順位・キューが決まる。
 *
 * 優先順位(小さいほど先): 1 管理者の操作 → 2 投稿の判定 → 3 情報提供の読み取り → 4 巡回 → 5 地域ページの紹介文(設計書9.3)
 */
enum AiPurpose: string
{
    use HasLabel;

    case ReviewText = 'review_text';
    case ReviewImage = 'review_image';
    case DraftFromUrl = 'draft_from_url';
    case Suggest = 'suggest';
    case Tip = 'tip';
    case Crawl = 'crawl';
    case RegionIntro = 'region_intro';
    case FactCheck = 'fact_check';
    case TakedownCheck = 'takedown_check';

    public function settingKey(): SettingKey
    {
        return match ($this) {
            self::ReviewText => SettingKey::AiModelsReviewText,
            self::ReviewImage => SettingKey::AiModelsReviewImage,
            self::DraftFromUrl => SettingKey::AiModelsDraft,
            self::Suggest => SettingKey::AiModelsSuggest,
            self::Tip => SettingKey::AiModelsTip,
            self::Crawl => SettingKey::AiModelsCrawl,
            self::RegionIntro => SettingKey::AiModelsRegionIntro,
            self::FactCheck => SettingKey::AiModelsFactCheck,
            self::TakedownCheck => SettingKey::AiModelsTakedownCheck,
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::DraftFromUrl, self::Suggest => 1,
            self::ReviewText, self::ReviewImage, self::TakedownCheck => 2,
            self::Tip => 3,
            self::Crawl => 4,
            self::RegionIntro, self::FactCheck => 5,
        };
    }

    /** キューの名前(優先順位ごと。スケジューラは小さい順に取り出す) */
    public function queue(): string
    {
        return 'ai-'.max(2, $this->priority());
    }

    /** 記録・学習をしない提供元だけに送る用途(個人情報保護法28条。削除依頼の照合だけ。設計書9.4) */
    public function denyDataCollection(): bool
    {
        return $this === self::TakedownCheck;
    }

    /** 画面から同期で呼ぶ用途(キューを通さない) */
    public function isSynchronous(): bool
    {
        return $this->priority() === 1;
    }
}
