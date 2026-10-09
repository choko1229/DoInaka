<?php

declare(strict_types=1);

namespace App\Services\Design;

use App\Enums\IllustVariant;
use Imagick;
use RuntimeException;

/**
 * 元の PNG(1536×1024)から wide / card / card-sm の WebP を作る。
 *
 * - wide: 上下を切って 1536×512(中央ではなく、上から160〜672px)
 * - card: 左右を切った中央の 1365×1024 を 1200×900 にする
 * - card-sm: card を 600×450 に縮める
 */
final class IllustImageBuilder
{
    private const SOURCE_WIDTH = 1536;

    private const SOURCE_HEIGHT = 1024;

    private const WIDE_TOP = 160;

    private const CARD_CROP_WIDTH = 1365;

    public function __construct(
        private readonly string $sourcePath,
        private readonly string $outputPath,
        private readonly int $quality = 80,
    ) {}

    public function hasSource(): bool
    {
        return is_dir($this->sourcePath) && $this->sourceFiles() !== [];
    }

    /**
     * @return list<string> 元の PNG のパス
     */
    public function sourceFiles(): array
    {
        $files = glob($this->sourcePath.DIRECTORY_SEPARATOR.'*.png') ?: [];
        sort($files);

        return $files;
    }

    /**
     * 1枚分の3種類を作る。
     *
     * @return list<string> 作った WebP のパス
     */
    public function build(string $pngPath): array
    {
        $name = pathinfo($pngPath, PATHINFO_FILENAME);
        $written = [];

        $source = new Imagick($pngPath);

        try {
            if ($source->getImageWidth() !== self::SOURCE_WIDTH || $source->getImageHeight() !== self::SOURCE_HEIGHT) {
                throw new RuntimeException(sprintf(
                    '%s は %d×%d ではありません(%d×%d)。',
                    basename($pngPath), self::SOURCE_WIDTH, self::SOURCE_HEIGHT,
                    $source->getImageWidth(), $source->getImageHeight(),
                ));
            }

            $wide = clone $source;
            $wide->cropImage(self::SOURCE_WIDTH, IllustVariant::Wide->height(), 0, self::WIDE_TOP);
            $written[] = $this->write($wide, IllustVariant::Wide, $name);

            $card = clone $source;
            $card->cropImage(self::CARD_CROP_WIDTH, self::SOURCE_HEIGHT, intdiv(self::SOURCE_WIDTH - self::CARD_CROP_WIDTH, 2), 0);
            $card->resizeImage(IllustVariant::Card->width(), IllustVariant::Card->height(), Imagick::FILTER_LANCZOS, 1);
            $small = clone $card;
            $written[] = $this->write($card, IllustVariant::Card, $name);

            $small->resizeImage(IllustVariant::CardSm->width(), IllustVariant::CardSm->height(), Imagick::FILTER_LANCZOS, 1);
            $written[] = $this->write($small, IllustVariant::CardSm, $name);
        } finally {
            $source->clear();
        }

        return $written;
    }

    private function write(Imagick $image, IllustVariant $variant, string $name): string
    {
        $directory = $this->outputPath.DIRECTORY_SEPARATOR.$variant->value;
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("{$directory} を作れません。");
        }

        $path = $directory.DIRECTORY_SEPARATOR.$name.'.webp';

        $image->setImagePage(0, 0, 0, 0);
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality($this->quality);
        $image->stripImage();
        $image->writeImage($path);
        $image->clear();

        return $path;
    }
}
