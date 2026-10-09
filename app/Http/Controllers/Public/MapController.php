<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Event;
use App\Models\Spot;
use App\Services\Region\RegionScope;
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
        foreach (Spot::query()->where('is_published', true)->whereIn('region_id', $ids)->whereNotNull('lat')->whereNotNull('lng')->limit(500)->get() as $spot) {
            $pins[] = ['type' => 'spot', 'title' => $spot->title, 'url' => $this->links->spot($spot), 'lat' => (float) $spot->lat, 'lng' => (float) $spot->lng];
        }
        foreach (Event::query()->where('is_published', true)->whereIn('region_id', $ids)->whereNotNull('lat')->whereNotNull('lng')
            ->whereHas('schedules', fn ($q) => $q->where('date', '>=', now()->toDateString()))->limit(500)->get() as $event) {
            $pins[] = ['type' => 'event', 'title' => $event->title, 'url' => $this->links->event($event), 'lat' => (float) $event->lat, 'lng' => (float) $event->lng];
        }

        $crumbs = [...$this->meta->regionCrumbs($region, false), ['name' => __('public.nav_map'), 'url' => null]];

        return view('public.map', [
            'meta' => $this->meta->page(__('public.map_title', ['region' => $region->name]), __('public.map_description', ['region' => $region->name]), $this->links->map($pref), $crumbs, noindex: true),
            'pref' => $pref,
            'region' => $region,
            'pins' => $pins,
            'center' => ['lat' => (float) ($region->lat ?? 34.34), 'lng' => (float) ($region->lng ?? 134.04)],
        ]);
    }
}
