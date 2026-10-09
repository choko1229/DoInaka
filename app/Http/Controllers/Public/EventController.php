<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Contracts\SearchEngine;
use App\Enums\CategoryTarget;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Event;
use App\Services\Public\RelatedContent;
use App\Services\Public\SearchQueryFactory;
use App\Services\Url\UrlCanonicalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EventController extends PublicController
{
    public function index(Request $request, string $pref, SearchQueryFactory $factory, SearchEngine $search): View
    {
        // ルートの引数は位置で渡されるので、名前で読む(サービスの注入と順序が混ざらないように)
        $mode = $request->route('mode');
        $category = $request->route('category');
        $mode = is_string($mode) ? $mode : null;
        $category = is_string($category) ? $category : null;
        $region = $this->pref($pref);

        $fixedCategory = null;
        if ($category !== null) {
            $fixedCategory = Category::query()->where('target', CategoryTarget::Event)->where('slug', $category)->where('is_active', true)->first();
            abort_if($fixedCategory === null, 404);
        }

        $query = $factory->fromRequest($request, $region, CategoryTarget::Event, $mode === 'weekend' ? 'weekend' : null, $fixedCategory?->id);
        $events = $search->events($query)->withQueryString();

        // 固定の絞り込みページ(今週末・カテゴリ別)は、それ自体が「条件なし」の入口。クエリが足されたら絞り込み扱い
        $fixedPath = match (true) {
            $mode === 'weekend' => $this->links->weekend($pref),
            $fixedCategory !== null => $this->links->category($pref, $fixedCategory->slug),
            default => $this->links->events($pref),
        };
        $queryFiltered = $request->query() !== [] && $this->hasFilterParams($request);
        $noindex = $queryFiltered || $events->total() < $this->meta->minIndexItems();

        $title = match (true) {
            $mode === 'weekend' => __('public.events_weekend_title', ['region' => $region->name]),
            $fixedCategory !== null => __('public.events_category_title', ['region' => $region->name, 'category' => $fixedCategory->name]),
            default => __('public.events_title', ['region' => $region->name]),
        };
        $crumbs = [...$this->meta->regionCrumbs($region, false), ['name' => __('public.nav_events'), 'url' => $mode === null && $fixedCategory === null ? null : $this->links->events($pref)]];
        if ($mode !== null || $fixedCategory !== null) {
            $crumbs[] = ['name' => $mode === 'weekend' ? __('public.weekend') : (string) $fixedCategory?->name, 'url' => null];
        }
        // ?page=2 だけは自分自身を canonical にする
        $queryString = $query->page > 1 && ! $queryFiltered ? 'page='.$query->page : null;

        $meta = $this->meta->page($title, __('public.events_description', ['region' => $region->name]), $fixedPath, $crumbs, $noindex, $queryString);

        return view('public.events.index', [
            'meta' => $meta,
            'region' => $region,
            'pref' => $pref,
            'events' => $events,
            'query' => $query,
            'categories' => Category::query()->where('target', CategoryTarget::Event)->where('is_active', true)->orderBy('sort_order')->get(),
            'heading' => $title,
            'basePath' => $fixedPath,
        ]);
    }

    public function show(Request $request, string $pref, string $segment, RelatedContent $related): View|RedirectResponse
    {
        $region = $this->pref($pref);
        $event = $this->resolveItem(Event::class, $region, $segment);
        if ($event instanceof RedirectResponse) {
            return $event;
        }

        $event->load(['schedules', 'sources.media', 'region.parent', 'category', 'tags', 'series', 'media']);
        $this->views->record($request, $event);

        $comments = Comment::query()->where('commentable_type', 'event')->where('commentable_id', $event->id)->where('status', 'published')
            ->whereNull('deleted_at')->with('user')->orderBy('id')->limit(100)->get();

        $sameSeries = $related->sameSeries($event);
        $next = $event->status->value === 'ended' || $event->lastDate()?->isPast() === true
            ? $sameSeries->first(fn (Event $e): bool => $e->lastDate()?->isFuture() === true || $e->lastDate()?->isToday() === true)
            : null;

        return view('public.events.show', [
            'meta' => $this->meta->event($event),
            'pref' => $pref,
            'event' => $event,
            'sameSeries' => $sameSeries,
            'nextEvent' => $next,
            'nearbyEvents' => $related->nearbyEvents($event),
            'nearbySpots' => $related->nearbySpots($event),
            'comments' => $comments,
            'shareUrl' => app(UrlCanonicalizer::class)->shareUrl($this->links->event($event)),
        ]);
    }

    private function hasFilterParams(Request $request): bool
    {
        foreach (['q', 'category', 'tag', 'when', 'from', 'to', 'past', 'lat', 'lng', 'r', 'sort'] as $key) {
            if ($request->query($key) !== null && $request->query($key) !== '') {
                return true;
            }
        }

        return false;
    }
}
