<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Contracts\SearchEngine;
use App\Enums\CategoryTarget;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Spot;
use App\Services\Public\RelatedContent;
use App\Services\Public\SearchQueryFactory;
use App\Services\Url\UrlCanonicalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SpotController extends PublicController
{
    public function index(Request $request, string $pref, SearchQueryFactory $factory, SearchEngine $search): View
    {
        $region = $this->pref($pref);
        $query = $factory->fromRequest($request, $region, CategoryTarget::Spot);
        $spots = $search->spots($query)->withQueryString();
        $noindex = $query->isFiltered() || $spots->total() < $this->meta->minIndexItems();
        $title = __('public.spots_title', ['region' => $region->name]);
        $crumbs = [...$this->meta->regionCrumbs($region, false), ['name' => __('public.nav_spots'), 'url' => null]];

        return view('public.spots.index', [
            'meta' => $this->meta->page($title, __('public.spots_description', ['region' => $region->name]), $this->links->spots($pref), $crumbs, $noindex),
            'pref' => $pref,
            'spots' => $spots,
            'query' => $query,
            'categories' => Category::query()->where('target', CategoryTarget::Spot)->where('is_active', true)->orderBy('sort_order')->get(),
            'heading' => $title,
            'basePath' => $this->links->spots($pref),
        ]);
    }

    public function show(Request $request, string $pref, string $segment, RelatedContent $related): View|RedirectResponse
    {
        $region = $this->pref($pref);
        $spot = $this->resolveItem(Spot::class, $region, $segment);
        if ($spot instanceof RedirectResponse) {
            return $spot;
        }

        $spot->load(['region.parent', 'category', 'tags', 'media']);
        $this->views->record($request, $spot);

        return view('public.spots.show', [
            'meta' => $this->meta->spot($spot),
            'pref' => $pref,
            'spot' => $spot,
            'nearbyEvents' => $related->nearbyEvents($spot),
            'nearbySpots' => $related->nearbySpots($spot),
            'comments' => Comment::query()->where('commentable_type', 'spot')->where('commentable_id', $spot->id)->where('status', 'published')->whereNull('deleted_at')->with('user')->orderBy('id')->limit(100)->get(),
            'shareUrl' => app(UrlCanonicalizer::class)->shareUrl($this->links->spot($spot)),
        ]);
    }
}
