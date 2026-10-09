<?php

declare(strict_types=1);

namespace App\Services\Region;

use App\Models\Region;
use App\Services\Web\FetchResult;
use App\Services\Web\HtmlText;
use App\Services\Web\UrlFetcher;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 地域ページの紹介文の情報元を集める(設計書9.8): 自治体公式サイトの概要・沿革ページ(regions.official_url)と、
 * Wikipedia(事実の確認だけに使い、文章は使わない)。番号を付けて返す(段落ごとの出典番号に使う)。
 */
final class RegionSourceCollector
{
    private const MAX_CHARS = 6000;

    public function __construct(private readonly UrlFetcher $fetcher) {}

    /** @return list<array{n: int, title: string, url: string|null, text: string}> */
    public function collect(Region $region): array
    {
        $sources = [];

        if ($region->official_url !== null && $region->official_url !== '') {
            $page = $this->fetcher->fetch($region->official_url);
            if ($page->status === FetchResult::OK && $page->text() !== '') {
                $sources[] = ['n' => count($sources) + 1, 'title' => (HtmlText::title($page->html) ?? $region->name.'の公式サイト'), 'url' => $region->official_url, 'text' => mb_substr($page->text(), 0, self::MAX_CHARS)];
            }
        }

        $wiki = $this->wikipedia($region);
        if ($wiki !== null) {
            $sources[] = ['n' => count($sources) + 1] + $wiki;
        }

        return $sources;
    }

    /** @return array{title: string, url: string, text: string}|null */
    private function wikipedia(Region $region): ?array
    {
        try {
            $response = Http::withUserAgent('DoinakaBot/1.0 (+https://do-inaka.net/about/)')->timeout(10)->acceptJson()->get('https://ja.wikipedia.org/w/api.php', [
                'action' => 'query', 'prop' => 'extracts', 'explaintext' => 1, 'exlimit' => 1, 'redirects' => 1,
                'titles' => $region->name, 'format' => 'json', 'formatversion' => 2,
            ]);
        } catch (Throwable) {
            return null;
        }

        $page = $response->json('query.pages.0');
        if (! $response->successful() || ! is_array($page) || ($page['missing'] ?? false) === true || ! is_string($page['extract'] ?? null) || $page['extract'] === '') {
            return null;
        }

        $title = is_string($page['title'] ?? null) ? $page['title'] : $region->name;

        return [
            'title' => 'Wikipedia「'.$title.'」',
            'url' => 'https://ja.wikipedia.org/wiki/'.rawurlencode(str_replace(' ', '_', $title)),
            'text' => mb_substr($page['extract'], 0, self::MAX_CHARS),
        ];
    }
}
