<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\Geo\Geocoder;
use Illuminate\Console\Command;

/**
 * 位置(緯度・経度)がなく、住所か会場名がある行事に、国土地理院の住所検索で位置を入れる(地図のピンのため)。
 * 一度に少しずつ(既定20件)。見つからなかったものは、次回また試す(結果は Geocoder が30日覚えるので、同じ検索は増えない)。
 */
final class EventsGeocode extends Command
{
    protected $signature = 'events:geocode {--limit=20 : 一度に調べる件数}';

    protected $description = '位置のない行事に、住所・会場名から緯度・経度を入れる';

    public function handle(Geocoder $geocoder): int
    {
        $done = 0;
        $events = Event::query()
            ->where('is_published', true)
            ->whereNull('lat')
            ->where(fn ($q) => $q->whereNotNull('address')->where('address', '!=', '')->orWhere(fn ($r) => $r->whereNotNull('venue_name')->where('venue_name', '!=', '')))
            ->with('region.parent')
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        foreach ($events as $event) {
            $query = (string) (($event->address ?? '') !== '' ? $event->address : $event->venue_name);
            $region = $event->region;
            $prefecture = $region === null ? null : ($region->parent_id === null ? $region : $region->parent);

            $found = $geocoder->find($query, $prefecture?->name);
            if ($found === null) {
                continue;
            }
            $event->forceFill(['lat' => $found['lat'], 'lng' => $found['lng']])->save();
            $done++;
        }

        $this->info($done.' 件に位置を入れました。');

        return self::SUCCESS;
    }
}
