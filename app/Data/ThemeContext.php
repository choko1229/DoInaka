<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Season;
use App\Enums\Theme;
use App\Enums\ThemePreference;

/**
 * いまの画面の配色(レイアウトの <html data-theme / data-season> に入れる)。
 */
final readonly class ThemeContext
{
    public function __construct(
        public Theme $theme,
        public Season $season,
        public ThemePreference $preference,
    ) {}
}
