<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Contracts\SearchEngine;
use App\Enums\RegionLevel;
use App\Models\Region;
use App\Services\Region\RegionPageQueue;
use App\Services\Region\RegionScope;
use App\Services\Search\SearchQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * 地域ページ(設計書9.8): /{pref}/、/{pref}/{city}/、/{pref}/{city}/{old}/。
 */
final class RegionController extends PublicController
{
    public function show(string $pref, SearchEngine $search, RegionScope $scope, RegionPageQueue $queue, ?string $city = null, ?string $old = null): View|RedirectResponse
    {
        $region = $this->pref($pref);

        if ($city !== null) {
            $region = $this->child($region, $city);
            if ($old !== null) {
                $region = $this->child($region, $old);
            }
        }

        // 紹介文がまだなければ、生成キューに1件だけ入れる(何度アクセスしても増えない)
        $queue->enqueue($region);

        $ids = $scope->ids($region);
        $events = $search->events(new SearchQuery(regionIds: $ids, perPage: 6));
        $spots = $search->spots(new SearchQuery(regionIds: $ids, perPage: 6));

        $children = $region->children()->where('is_active', true)->get();
        $alsoChildren = $region->alsoChildren()->where('regions.is_active', true)->get();
        $former = Region::query()->where('former_parent_id', $region->id)->where('is_active', true)->orderBy('sort_order')->get();

        $indexable = $queue->isIndexable($region);
        $path = $this->links->region($region);
        $crumbs = $this->meta->regionCrumbs($region);
        $intro = $region->intro_body;
        $description = $indexable && $intro !== null ? $intro : __('public.region_description', ['region' => $region->name]);

        return view('public.region', [
            'meta' => $this->meta->page(__('public.region_title', ['region' => $region->name]), $description, $path, $crumbs, noindex: ! $indexable),
            'pref' => $pref,
            'region' => $region,
            'children' => $children,
            'alsoChildren' => $alsoChildren,
            'former' => $former,
            'events' => $events,
            'spots' => $spots,
            'indexable' => $indexable,
            'isPref' => $region->level === RegionLevel::Prefecture,
            'shikoku' => $region->level === RegionLevel::Prefecture
                ? Region::query()->whereNull('parent_id')->where('is_active', true)->whereIn('slug', ['tokushima', 'ehime', 'kochi', 'kagawa'])->where('id', '!=', $region->id)->orderBy('sort_order')->get()
                : collect(),
            'eventsPath' => $this->links->events($pref).'?'.http_build_query([]),
        ]);
    }

    /** 次の階層のスラッグを引く。存在しない・無効なら 404 */
    private function child(Region $parent, string $slug): Region
    {
        $child = $parent->children()->where('slug', $slug)->where('is_active', true)->first();
        abort_if($child === null, 404);

        return $child;
    }
}
