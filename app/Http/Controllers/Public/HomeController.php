<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Contracts\SearchEngine;
use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Services\Search\SearchQuery;
use App\Services\Url\PublicLinks;
use App\Services\Url\UrlCanonicalizer;
use App\Support\PageMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

/**
 * トップ。今週末・近日のイベントと、人気のスポットを並べる。重いクエリは10分キャッシュする。
 */
final class HomeController extends Controller
{
    public function __invoke(SearchEngine $search, PublicLinks $links, UrlCanonicalizer $urls): View
    {
        $pref = Region::query()->whereNull('parent_id')->where('is_active', true)->where('accepts_posts', true)->orderBy('sort_order')->orderBy('id')->first()
            ?? Region::query()->whereNull('parent_id')->where('is_active', true)->orderBy('id')->first();

        $weekend = $this->cached('weekend', $pref, fn () => $search->events(new SearchQuery(regionIds: $pref === null ? null : [$pref->id], preset: 'weekend', perPage: 6))->items());
        $upcoming = $this->cached('upcoming', $pref, fn () => $search->events(new SearchQuery(regionIds: $pref === null ? null : [$pref->id], perPage: 6))->items());
        $spots = $this->cached('spots', $pref, fn () => $search->spots(new SearchQuery(regionIds: $pref === null ? null : [$pref->id], sort: 'popular', perPage: 6))->items());

        $meta = new PageMeta(
            title: config()->string('app.name').' — '.__('layout.tagline'),
            description: __('public.home_description'),
            canonical: $urls->canonicalUrl('/'),
        );

        return view('public.home', [
            'meta' => $meta,
            'pref' => $pref->slug ?? 'kagawa',
            'prefRegion' => $pref,
            'weekend' => $weekend,
            'upcoming' => $upcoming,
            'spots' => $spots,
            'links' => $links,
            'prefectures' => Region::query()->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /**
     * @param  \Closure(): array<int, mixed>  $resolve
     * @return array<int, mixed>
     */
    private function cached(string $name, ?Region $pref, \Closure $resolve): array
    {
        // テスト中は毎回引く(キャッシュが別のテストの結果を返さないように)
        if (app()->environment('testing')) {
            return $resolve();
        }

        return Cache::remember("top:{$name}:".($pref->id ?? 0).':'.now()->format('YmdHi'), 600, $resolve);
    }
}
