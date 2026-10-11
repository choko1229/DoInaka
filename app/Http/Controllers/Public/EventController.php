<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Contracts\SearchEngine;
use App\Enums\CategoryTarget;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Region;
use App\Services\Public\EventCalendar;
use App\Services\Public\RelatedContent;
use App\Services\Public\SearchQueryFactory;
use App\Services\Search\SearchQuery;
use App\Services\Url\UrlCanonicalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        // カレンダー表示(?view=calendar&month=2026-10)。同じ絞り込み条件で、月の格子に並べる
        $view = $request->query('view') === 'calendar' ? 'calendar' : 'list';
        $month = $request->query('month');
        $calendar = $view === 'calendar' ? app(EventCalendar::class)->month($query, is_string($month) ? $month : null) : null;

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
            'view' => $view,
            'calendar' => $calendar,
            'areas' => Region::query()->where('parent_id', $region->id)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'area' => is_string($request->query('area')) ? $request->query('area') : null,
            'when' => $this->whenChoice($request, $query),
        ]);
    }

    /** 「いつ」の選択(今日・今週末・今月・期間を指定)。何も選んでいなければ null */
    private function whenChoice(Request $request, SearchQuery $query): ?string
    {
        $when = $request->query('when');
        if ($query->preset !== null) {
            return $query->preset;
        }
        if (is_string($when) && $when === 'month') {
            return 'month';
        }

        return $query->dateFrom !== null || $query->dateTo !== null ? 'range' : null;
    }

    public function show(Request $request, string $pref, string $segment, RelatedContent $related): View|RedirectResponse
    {
        $region = $this->pref($pref);
        $event = $this->resolveItem(Event::class, $region, $segment);
        if ($event instanceof RedirectResponse) {
            return $event;
        }
        $this->abortIfHeld($event);

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

    /** 「カレンダーに追加」: 開催日ごとの予定を iCalendar(.ics)で返す。中止の日は入れない */
    public function ics(string $pref, string $segment): Response|RedirectResponse
    {
        $region = $this->pref($pref);
        $event = $this->resolveItem(Event::class, $region, $segment);
        if ($event instanceof RedirectResponse) {
            return $event;
        }
        $this->abortIfHeld($event);
        $event->load(['schedules']);

        $escape = static fn (string $text): string => str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\\;', '\\,', '\\n', '\\n'], $text);
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//do-inaka.net//JA', 'CALSCALE:GREGORIAN'];
        foreach ($event->schedules->where('is_cancelled', false) as $s) {
            $day = $s->date->format('Ymd');
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:event-'.$event->id.'-'.$s->id.'@do-inaka.net';
            $lines[] = 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z');
            if ($s->start_time !== null && ! $s->is_all_day) {
                $start = str_replace(':', '', substr($s->start_time, 0, 5)).'00';
                $end = $s->end_time !== null ? str_replace(':', '', substr($s->end_time, 0, 5)).'00' : null;
                $lines[] = 'DTSTART;TZID=Asia/Tokyo:'.$day.'T'.$start;
                if ($end !== null) {
                    $lines[] = 'DTEND;TZID=Asia/Tokyo:'.$day.'T'.$end;
                }
            } else {
                $lines[] = 'DTSTART;VALUE=DATE:'.$day;
            }
            $lines[] = 'SUMMARY:'.$escape($event->title);
            if ($event->venue_name) {
                $lines[] = 'LOCATION:'.$escape($event->venue_name);
            }
            $lines[] = 'URL:'.$this->links->event($event);
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="event-'.$event->id.'.ics"',
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
