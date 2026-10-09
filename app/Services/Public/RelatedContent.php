<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Contracts\SearchEngine;
use App\Models\Event;
use App\Models\Spot;
use App\Services\Search\SearchQuery;
use Illuminate\Support\Collection;

/**
 * 関連・近くの情報(設計書11.5)。同じ行事の別の開催回、近くのイベントとスポット(半径10km)。
 */
final class RelatedContent
{
    public const NEARBY_KM = 10.0;

    public function __construct(private readonly SearchEngine $search) {}

    /**
     * 同じ行事の、ほかの開催回(新しい順)
     *
     * @return Collection<int, Event>
     */
    public function sameSeries(Event $event): Collection
    {
        return Event::query()
            ->where('series_id', $event->series_id)->where('id', '!=', $event->id)->where('is_published', true)
            ->with(['schedules', 'region.parent', 'tags', 'media'])
            ->get()
            ->sortByDesc(fn (Event $e) => $e->firstDate()?->toDateString() ?? '')
            ->values();
    }

    /** @return Collection<int, Event> */
    public function nearbyEvents(Event|Spot $origin, int $limit = 4): Collection
    {
        if ($origin->lat === null || $origin->lng === null) {
            return new Collection;
        }
        $page = $this->search->events(new SearchQuery(lat: (float) $origin->lat, lng: (float) $origin->lng, radiusKm: self::NEARBY_KM, sort: 'distance', perPage: $limit + 1));

        /** @var list<Event> $out */
        $out = [];
        foreach ($page->items() as $item) {
            if (! ($origin instanceof Event && $item->id === $origin->id) && count($out) < $limit) {
                $out[] = $item;
            }
        }

        return new Collection($out);
    }

    /** @return Collection<int, Spot> */
    public function nearbySpots(Event|Spot $origin, int $limit = 4): Collection
    {
        if ($origin->lat === null || $origin->lng === null) {
            return new Collection;
        }
        $page = $this->search->spots(new SearchQuery(lat: (float) $origin->lat, lng: (float) $origin->lng, radiusKm: self::NEARBY_KM, sort: 'distance', perPage: $limit + 1));

        /** @var list<Spot> $out */
        $out = [];
        foreach ($page->items() as $item) {
            if (! ($origin instanceof Spot && $item->id === $origin->id) && count($out) < $limit) {
                $out[] = $item;
            }
        }

        return new Collection($out);
    }
}
