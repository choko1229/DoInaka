<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\IllustVariant;
use App\Enums\Place;
use App\Enums\Season;
use App\Enums\Theme;

/**
 * 場所×季節×時間帯の1枚。
 */
final readonly class Illust
{
    public function __construct(
        public Place $place,
        public Season $season,
        public Theme $theme,
    ) {}

    /** ファイル名(拡張子なし)。例: island-summer-evening */
    public function name(): string
    {
        return "{$this->place->value}-{$this->season->value}-{$this->theme->value}";
    }

    /** resources 以下の WebP の相対パス */
    public function resourcePath(IllustVariant $variant): string
    {
        return "images/illust/{$variant->value}/{$this->name()}.webp";
    }

    public function exists(IllustVariant $variant): bool
    {
        return is_file(config()->string('illust.output_path').DIRECTORY_SEPARATOR.$variant->value.DIRECTORY_SEPARATOR.$this->name().'.webp');
    }
}
