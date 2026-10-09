<?php

declare(strict_types=1);

namespace App\Services\Design;

use App\Data\Illust;
use App\Enums\Place;
use App\Enums\Season;
use App\Enums\SettingKey;
use App\Enums\Theme;
use App\Services\Setting\SettingsService;
use Carbon\CarbonInterface;

/**
 * ページに合わせてイラスト1枚を選ぶ(docs/illustrations.md)。
 *
 * - 季節と時間帯は今の配色に合わせる
 * - 場所は、分類・地域のスラッグから設定 illust.place_map の対応表で決める
 * - 分からなければ4枚からランダム。ただしページ ID と日付から決まる乱数なので、
 *   同じページは1日の中では同じ場所(ページキャッシュと矛盾しない)
 */
final class IllustSelector
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @param  list<string>  $hints  そのページの分類・地域のスラッグ
     */
    public function select(string $pageKey, array $hints, Theme $theme, Season $season, CarbonInterface $date): Illust
    {
        $place = $this->placeFromHints($hints) ?? $this->randomPlace($pageKey, $date);

        return new Illust($place, $season, $theme);
    }

    /**
     * @param  list<string>  $hints
     */
    public function placeFromHints(array $hints): ?Place
    {
        $map = $this->settings->array(SettingKey::IllustPlaceMap);

        foreach ($hints as $hint) {
            foreach ($map as $placeName => $slugs) {
                $place = Place::tryFrom((string) $placeName);
                if ($place !== null && is_array($slugs) && in_array($hint, $slugs, true)) {
                    return $place;
                }
            }
        }

        return null;
    }

    private function randomPlace(string $pageKey, CarbonInterface $date): Place
    {
        $places = Place::cases();
        $seed = crc32($pageKey.'|'.$date->format('Y-m-d'));

        return $places[$seed % count($places)];
    }
}
