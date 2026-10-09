<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Enums\SettingKey;
use App\Models\AdSlot;
use App\Services\Setting\SettingsService;
use Carbon\CarbonInterface;

/**
 * どの広告を出すか(設計書14章・フェーズ7)。
 *
 * - AdSense(Google の広告だけ)は、場所ごとに ON/OFF。地図・投稿・マイページには、設定があっても出さない
 * - PR 枠は、期間(開始〜終了)の中だけ。必ず「PR」と表示する(表示は x-ad コンポーネント)
 */
final class AdSelector
{
    /** 広告を出してよい場所(地図・投稿フォーム・マイページ・管理画面・ログインは含めない) */
    public const POSITIONS = ['top', 'list', 'event_detail', 'spot_detail', 'article_detail'];

    public function __construct(private readonly SettingsService $settings) {}

    public static function isAllowed(string $position): bool
    {
        return in_array($position, self::POSITIONS, true);
    }

    /** 期間の中にある、有効な PR 枠(複数あれば、いちばん新しいもの) */
    public function pr(string $position, ?CarbonInterface $now = null): ?AdSlot
    {
        if (! self::isAllowed($position)) {
            return null;
        }
        $now ??= now();

        return AdSlot::query()->where('kind', 'pr')->where('position', $position)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->latest('id')->first();
    }

    /** AdSense を出すなら、クライアント ID(出さないなら null) */
    public function adsense(string $position): ?string
    {
        if (! self::isAllowed($position) || ! $this->settings->bool(SettingKey::AdsEnabled)) {
            return null;
        }

        $client = trim($this->settings->string(SettingKey::AdsAdsenseClientId));
        if ($client === '' || preg_match('/^ca-pub-\d{10,20}$/', $client) !== 1) {
            return null;
        }

        $on = AdSlot::query()->where('kind', 'adsense')->where('position', $position)->where('is_active', true)->exists();

        return $on ? $client : null;
    }
}
