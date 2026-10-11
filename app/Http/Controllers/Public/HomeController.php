<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Contracts\SearchEngine;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Event;
use App\Models\Region;
use App\Models\Spot;
use App\Services\Design\ThemeResolver;
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

        $articles = $this->cached('articles', $pref, Article::class, fn () => $search->articles(new SearchQuery(regionIds: $ids, sort: 'date', perPage: 3))->items(), ['region.parent', 'tags', 'media']);

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
            'articles' => $articles,
            'seasonPicks' => $this->seasonPicks($search, $ids, $pref),
            'areas' => $this->areas($search, $scope, $pref),
            'links' => $links,
            'prefectures' => Region::query()->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /**
     * 「いまの季節のおすすめ」: 季節ごとの検索の言葉と、その件数(これからのイベント)。
     *
     * @param  list<int>|null  $ids
     * @return list<array{label: string, q: string, count: int}>
     */
    private function seasonPicks(SearchEngine $search, ?array $ids, ?Region $pref): array
    {
        $season = app(ThemeResolver::class)->seasonAt(now());
        /** @var list<array{label: string, q: string}> $picks */
        $picks = config()->array('home.season_picks.'.$season->value, []);

        return Cache::remember('top:picks:'.$season->value.':'.($pref->id ?? 0), app()->environment('testing') ? 0 : 600, fn (): array => array_map(fn (array $pick): array => [
            'label' => $pick['label'],
            'q' => $pick['q'],
            'count' => $search->events(new SearchQuery(regionIds: $ids, q: $pick['q'], perPage: 1))->total(),
        ], $picks));
    }

    /**
     * 「エリアから探す」: 県の中の市町を、これからのイベントが多い順に。
     *
     * @return array{items: list<array{name: string, count: int, href: string}>, total: int}
     */
    private function areas(SearchEngine $search, RegionScope $scope, ?Region $pref): array
    {
        if ($pref === null) {
            return ['items' => [], 'total' => 0];
        }

        return Cache::remember('top:areas:'.$pref->id, app()->environment('testing') ? 0 : 600, function () use ($search, $scope, $pref): array {
            $cities = Region::query()->where('parent_id', $pref->id)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
            /** @var list<array{name: string, count: int, href: string}> $rows */
            $rows = $cities->map(fn (Region $city): array => [
                'name' => $city->name,
                'count' => $search->events(new SearchQuery(regionIds: $scope->ids($city), perPage: 1))->total(),
                'href' => app(PublicLinks::class)->region($city),
            ])->sortByDesc('count')->take(config()->integer('home.areas', 5))->values()->all();

            return ['items' => $rows, 'total' => $cities->count()];
        });
    }

    /**
     * 結果の ID だけをキャッシュし、表示のたびにモデルを読み直す(キャッシュにオブジェクトは入れない)。
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<T>  $model
     * @param  \Closure(): array<int, mixed>  $resolve
     * @param  list<string>  $with  読み込む関係
     * @return list<T>
     */
    private function cached(string $name, ?Region $pref, string $model, \Closure $resolve, array $with = ['region.parent', 'category', 'tags', 'media']): array
    {
        $load = static fn (): array => collect($resolve())->map(fn (mixed $m): mixed => $m instanceof Model ? $m->getKey() : null)->filter()->values()->all();

        // テスト中は毎回引く(キャッシュが別のテストの結果を返さないように)
        $ids = app()->environment('testing') ? $load() : Cache::remember("top:{$name}:".($pref->id ?? 0).':'.now()->format('YmdH').intdiv((int) now()->format('i'), 10), 600, $load);
        if ($ids === []) {
            return [];
        }

        $byId = $model::query()->with($with)->whereIn('id', $ids)->get()->keyBy('id');
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
