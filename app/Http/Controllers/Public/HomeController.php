<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Contracts\SearchEngine;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Region;
use App\Models\Spot;
use App\Services\Region\RegionScope;
use App\Services\Search\SearchQuery;
use App\Services\Url\PublicLinks;
use App\Services\Url\UrlCanonicalizer;
use App\Support\PageMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * トップ。今週末・近日のイベントと、人気のスポットを並べる。重いクエリは10分キャッシュする。
 */
final class HomeController extends Controller
{
    public function __invoke(SearchEngine $search, PublicLinks $links, UrlCanonicalizer $urls, RegionScope $scope): View
    {
        $pref = Region::query()->whereNull('parent_id')->where('is_active', true)->where('accepts_posts', true)->orderByDesc('crawl_enabled')->orderBy('sort_order')->orderBy('id')->first()
            ?? Region::query()->whereNull('parent_id')->where('is_active', true)->orderBy('id')->first();

        // 県の中すべて(市町・旧町村を含む)から選ぶ
        $ids = $pref === null ? null : $scope->ids($pref);

        $weekend = $this->cached('weekend', $pref, Event::class, fn () => $search->events(new SearchQuery(regionIds: $ids, preset: 'weekend', perPage: 6))->items());
        $upcoming = $this->cached('upcoming', $pref, Event::class, fn () => $search->events(new SearchQuery(regionIds: $ids, perPage: 6))->items());
        $spots = $this->cached('spots', $pref, Spot::class, fn () => $search->spots(new SearchQuery(regionIds: $ids, sort: 'popular', perPage: 6))->items());

        $meta = new PageMeta(
            title: __('layout.tagline'),
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
     * 結果の ID だけをキャッシュし、表示のたびにモデルを読み直す(キャッシュにオブジェクトは入れない)。
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<T>  $model
     * @param  \Closure(): array<int, mixed>  $resolve
     * @return list<T>
     */
    private function cached(string $name, ?Region $pref, string $model, \Closure $resolve): array
    {
        $load = static fn (): array => collect($resolve())->map(fn (mixed $m): mixed => $m instanceof Model ? $m->getKey() : null)->filter()->values()->all();

        // テスト中は毎回引く(キャッシュが別のテストの結果を返さないように)
        $ids = app()->environment('testing') ? $load() : Cache::remember("top:{$name}:".($pref->id ?? 0).':'.now()->format('YmdHi'), 600, $load);
        if ($ids === []) {
            return [];
        }

        $byId = $model::query()->with(['region.parent', 'category', 'tags'])->whereIn('id', $ids)->get()->keyBy('id');
        /** @var list<T> $out */
        $out = [];
        foreach ($ids as $id) {
            $item = is_int($id) ? $byId->get($id) : null;
            if ($item !== null) {
                $out[] = $item;
            }
        }

        return $out;
    }
}
