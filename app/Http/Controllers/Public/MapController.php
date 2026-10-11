<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Event;
use App\Models\Spot;
use App\Services\Region\RegionScope;
use App\Support\DateText;
use Illuminate\Contracts\View\View;

/**
 * 地図(Leaflet・地理院タイル)。ピンはサーバーが渡す JSON から立てる。
 */
final class MapController extends PublicController
{
    public function index(string $pref, RegionScope $scope): View
    {
        $region = $this->pref($pref);
        $ids = $scope->ids($region);

        $pins = [];
        foreach (Spot::query()->where('is_published', true)->whereIn('region_id', $ids)->whereNotNull('lat')->whereNotNull('lng')->with(['region', 'category'])->limit(500)->get() as $spot) {
            $pins[] = ['type' => 'spot', 'title' => $spot->title, 'url' => $this->links->spot($spot), 'lat' => (float) $spot->lat, 'lng' => (float) $spot->lng, 'label' => __('public.nav_spots'), 'area' => $spot->region?->name, 'category' => $spot->category?->name, 'weekend' => false];
        }
        foreach (Event::query()->where('is_published', true)->whereIn('region_id', $ids)->whereNotNull('lat')->whereNotNull('lng')
            ->whereHas('schedules', fn ($q) => $q->where('date', '>=', now()->toDateString()))->with(['region', 'category', 'schedules'])->limit(500)->get() as $event) {
            $weekend = $event->schedules->contains(fn ($s): bool => $s->date->isWeekend() && $s->date->lte(now()->endOfWeek()->addDay()) && $s->date->gte(now()->startOfDay()));
            $pins[] = ['type' => 'event', 'title' => $event->title, 'url' => $this->links->event($event), 'lat' => (float) $event->lat, 'lng' => (float) $event->lng, 'label' => DateText::range($event), 'area' => $event->region?->name, 'category' => $event->category?->name, 'weekend' => $weekend];
        }

        $crumbs = [];

        return view('public.map', [
            'meta' => $this->meta->page(__('public.map_title', ['region' => $region->name]), __('public.map_description', ['region' => $region->name]), $this->links->map($pref), $crumbs, noindex: true),
            'pref' => $pref,
            'region' => $region,
            'pins' => $pins,
            'categories' => collect($pins)->pluck('category')->filter()->countBy()->sortDesc()->keys()->take(1)->all(),
            'center' => ['lat' => (float) ($region->lat ?? 34.34), 'lng' => (float) ($region->lng ?? 134.04)],
        ]);
    }
}
