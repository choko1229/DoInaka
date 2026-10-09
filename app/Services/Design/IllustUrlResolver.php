<?php

declare(strict_types=1);

namespace App\Services\Design;

use App\Data\Illust;
use App\Enums\IllustVariant;
use Illuminate\Support\Facades\Vite;
use Throwable;

/**
 * イラストの WebP の URL を引く。WebP がない・ビルドに入っていないときは null
 * (画面は無地の背景色のままになり、エラーにしない)。
 *
 * 開発ではリポジトリの WebP(resources/images/illust)を見る。リリースZIPには元の WebP を入れず
 * (ビルド済みの public/build と二重になるため)、Vite のマニフェストにあるかで判断する。
 */
final class IllustUrlResolver
{
    /** @var array<string, mixed>|null */
    private ?array $manifest = null;

    public function url(Illust $illust, IllustVariant $variant): ?string
    {
        if (! $this->available($illust, $variant)) {
            return null;
        }

        try {
            return Vite::asset('resources/'.$illust->resourcePath($variant));
        } catch (Throwable) {
            return null;
        }
    }

    private function available(Illust $illust, IllustVariant $variant): bool
    {
        return $illust->exists($variant) || array_key_exists('resources/'.$illust->resourcePath($variant), $this->manifest());
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $path = public_path('build/manifest.json');
        $decoded = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        /** @var array<string, mixed> $manifest */
        $manifest = is_array($decoded) ? $decoded : [];

        return $this->manifest = $manifest;
    }
}
