<?php

declare(strict_types=1);

namespace App\Services\Update;

use App\Contracts\ReleaseSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class GitHubReleaseSource implements ReleaseSource
{
    public function releases(string $repository): array
    {
        if (preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repository) !== 1) {
            throw new RuntimeException(__('update.invalid_repository'));
        }

        $response = Http::acceptJson()
            ->withUserAgent('DoInaka-Updater')
            ->timeout(15)
            ->retry(2, 500, throw: false)
            ->get("https://api.github.com/repos/{$repository}/releases", ['per_page' => 30]);

        if (! $response->successful()) {
            throw new RuntimeException(__('update.github_failed', ['status' => $response->status()]));
        }

        $releases = [];

        /** @var mixed $item */
        foreach ((array) $response->json() as $item) {
            $release = is_array($item) ? $this->toRelease($item) : null;
            if ($release !== null) {
                $releases[] = $release;
            }
        }

        usort($releases, fn (ReleaseInfo $a, ReleaseInfo $b): int => $b->version->compare($a->version));

        return $releases;
    }

    /**
     * @param  array<array-key, mixed>  $item
     */
    private function toRelease(array $item): ?ReleaseInfo
    {
        if (($item['draft'] ?? false) === true) {
            return null;
        }

        $tag = $item['tag_name'] ?? null;
        $version = is_string($tag) ? Version::parse($tag) : null;
        if ($version === null) {
            return null;
        }

        foreach ((array) ($item['assets'] ?? []) as $asset) {
            if (! is_array($asset)) {
                continue;
            }
            $name = $asset['name'] ?? null;
            $url = $asset['browser_download_url'] ?? null;
            if (! is_string($name) || ! is_string($url) || preg_match('/^doinaka-v[\d.]+\.zip$/', $name) !== 1) {
                continue;
            }
            // 配布元は GitHub の https だけ
            if (! str_starts_with($url, 'https://github.com/')) {
                continue;
            }

            $digest = $asset['digest'] ?? null;
            $sha256 = is_string($digest) && preg_match('/^sha256:([0-9a-fA-F]{64})$/', $digest, $m) === 1 ? strtolower($m[1]) : null;
            $published = $item['published_at'] ?? null;

            return new ReleaseInfo(
                $version,
                is_string($item['name'] ?? null) ? $item['name'] : (string) $tag,
                ($item['prerelease'] ?? false) === true,
                is_string($item['body'] ?? null) ? $item['body'] : '',
                is_string($item['html_url'] ?? null) ? $item['html_url'] : '',
                $name,
                $url,
                $sha256,
                is_int($asset['size'] ?? null) ? $asset['size'] : null,
                is_string($published) ? CarbonImmutable::parse($published) : null,
            );
        }

        return null;
    }
}
