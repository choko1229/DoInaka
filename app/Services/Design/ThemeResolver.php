<?php

declare(strict_types=1);

namespace App\Services\Design;

use App\Enums\Season;
use App\Enums\Theme;
use App\Enums\ThemePreference;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * 時刻と季節から配色を決める(日本時間)。
 *
 * 朝 5:00〜10:00 / 昼 10:00〜16:00 / 夕 16:00〜19:00 / 夜 19:00〜5:00。
 * 春 3〜5月 / 夏 6〜8月 / 秋 9〜11月 / 冬 12〜2月。
 */
final class ThemeResolver
{
    public const TIMEZONE = 'Asia/Tokyo';

    public function themeAt(CarbonInterface $at): Theme
    {
        $hour = $this->inJapan($at)->hour;

        return match (true) {
            $hour >= 5 && $hour < 10 => Theme::Morning,
            $hour >= 10 && $hour < 16 => Theme::Day,
            $hour >= 16 && $hour < 19 => Theme::Evening,
            default => Theme::Night,
        };
    }

    public function seasonAt(CarbonInterface $at): Season
    {
        return match ($this->inJapan($at)->month) {
            3, 4, 5 => Season::Spring,
            6, 7, 8 => Season::Summer,
            9, 10, 11 => Season::Autumn,
            default => Season::Winter,
        };
    }

    /**
     * 利用者の選択を反映した配色。昼固定・夜固定なら時刻に関係なくその配色にする。
     */
    public function resolve(ThemePreference $preference, CarbonInterface $at): Theme
    {
        return match ($preference) {
            ThemePreference::Day => Theme::Day,
            ThemePreference::Night => Theme::Night,
            ThemePreference::Auto => $this->themeAt($at),
        };
    }

    private function inJapan(CarbonInterface $at): CarbonImmutable
    {
        return CarbonImmutable::instance($at)->setTimezone(self::TIMEZONE);
    }
}
