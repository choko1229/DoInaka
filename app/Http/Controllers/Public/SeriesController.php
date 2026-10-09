<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Event;
use App\Models\EventSeries;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class SeriesController extends PublicController
{
    public function show(string $pref, string $segment): View|RedirectResponse
    {
        $region = $this->pref($pref);
        // 行事マスタには公開フラグがない。公開された開催回が1件もなければ 404
        $series = $this->resolveItem(EventSeries::class, $region, $segment, false);
        if ($series instanceof RedirectResponse) {
            return $series;
        }

        $events = Event::query()->where('series_id', $series->id)->where('is_published', true)->with(['schedules', 'region.parent'])->get()
            ->sortByDesc(fn (Event $e) => $e->firstDate()?->toDateString() ?? '')->values();
        abort_if($events->isEmpty(), 404);

        $seriesRegion = $series->region()->firstOrFail();
        $crumbs = [...$this->meta->regionCrumbs($seriesRegion, false), ['name' => $series->title, 'url' => null]];

        return view('public.series.show', [
            'meta' => $this->meta->page($series->title, $series->summary, $this->links->series($series), $crumbs),
            'pref' => $this->links->prefSlug($seriesRegion),
            'series' => $series,
            'events' => $events,
        ]);
    }
}
