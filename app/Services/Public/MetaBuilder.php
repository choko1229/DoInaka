<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Enums\EventStatus;
use App\Enums\SettingKey;
use App\Models\Article;
use App\Models\Event;
use App\Models\Region;
use App\Models\Spot;
use App\Services\Setting\SettingsService;
use App\Services\Url\PublicLinks;
use App\Services\Url\UrlCanonicalizer;
use App\Support\PageMeta;
use Illuminate\Support\Str;

/**
 * ページごとのタイトル・説明・canonical・構造化データ・パンくずを作る(設計書15章)。
 * canonical と og:url は、いつもメインのホスト(ド田舎.net)を指す。
 */
final class MetaBuilder
{
    public function __construct(
        private readonly UrlCanonicalizer $urls,
        private readonly PublicLinks $links,
        private readonly SettingsService $settings,
    ) {}

    /** @return list<array{name: string, url: string|null}> */
    public function regionCrumbs(Region $region, bool $self = true): array
    {
        $chain = [];
        $node = $region;
        $guard = 0;
        while ($guard++ < 5) {
            array_unshift($chain, $node);
            if ($node->parent_id === null) {
                break;
            }
            $node = $node->parent()->firstOrFail();
        }

        $crumbs = [['name' => __('public.top'), 'url' => '/']];
        foreach ($chain as $i => $r) {
            $last = $self && $i === count($chain) - 1;
            $crumbs[] = ['name' => $r->name, 'url' => $last ? null : $this->links->region($r)];
        }

        return $crumbs;
    }

    /**
     * @param  list<array{name: string, url: string|null}>  $crumbs
     * @return array<string, mixed>
     */
    public function breadcrumbLd(array $crumbs): array
    {
        $items = [];
        foreach ($crumbs as $i => $c) {
            $item = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name']];
            if ($c['url'] !== null) {
                $item['item'] = $this->urls->canonicalUrl($c['url']);
            }
            $items[] = $item;
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    /**
     * @param  list<array{name: string, url: string|null}>  $crumbs
     * @param  list<array<string, mixed>>  $extraLd
     */
    public function page(string $title, ?string $description, string $path, array $crumbs = [], bool $noindex = false, ?string $query = null, string $ogType = 'website', array $extraLd = []): PageMeta
    {
        $ld = $extraLd;
        if (count($crumbs) > 1) {
            $ld[] = $this->breadcrumbLd($crumbs);
        }

        return new PageMeta(
            title: $title,
            description: $description,
            canonical: $this->urls->canonicalUrl($path, $query),
            noindex: $noindex,
            breadcrumbs: $crumbs,
            ogType: $ogType,
            jsonLd: $ld,
        );
    }

    public function minIndexItems(): int
    {
        return $this->settings->int(SettingKey::SeoIndexMinItems);
    }

    public function event(Event $event): PageMeta
    {
        $region = $event->region()->firstOrFail();
        $path = $this->links->event($event);
        $crumbs = [...$this->regionCrumbs($region, false), ['name' => __('public.nav_events'), 'url' => $this->links->events($this->links->prefSlug($region))], ['name' => $event->title, 'url' => null]];
        $first = $event->schedules->first();
        $when = $first === null ? '' : $first->date->isoFormat('M月D日').'、';
        $description = $when.$region->name.'のイベント。'.($event->body ?? '');

        return $this->page($event->title, $description, $path, $crumbs, ogType: 'article', extraLd: [$this->eventLd($event, $region)]);
    }

    /** @return array<string, mixed> */
    public function eventLd(Event $event, Region $region): array
    {
        $first = $event->schedules->first();
        $last = $event->schedules->last();
        $ld = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->title,
            'url' => $this->urls->canonicalUrl($this->links->event($event)),
            'eventStatus' => match (true) {
                $event->status === EventStatus::Cancelled => 'https://schema.org/EventCancelled',
                $event->is_postponed => 'https://schema.org/EventRescheduled',
                default => 'https://schema.org/EventScheduled',
            },
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        ];
        if ($first !== null) {
            $ld['startDate'] = $this->iso($first->date->toDateString(), $first->start_time);
        }
        if ($last !== null) {
            $ld['endDate'] = $this->iso($last->date->toDateString(), $last->end_time ?? $last->start_time);
        }
        if ($event->body !== null && $event->body !== '') {
            $ld['description'] = Str::limit($event->body, 300);
        }

        $place = ['@type' => 'Place', 'name' => $event->venue_name ?? $region->name, 'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'JP', 'addressRegion' => $region->name, 'streetAddress' => $event->address ?? '']];
        if ($event->lat !== null && $event->lng !== null) {
            $place['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $event->lat, 'longitude' => (float) $event->lng];
        }
        $ld['location'] = $place;

        return $ld;
    }

    public function spot(Spot $spot): PageMeta
    {
        $region = $spot->region()->firstOrFail();
        $pref = $this->links->prefSlug($region);
        $crumbs = [...$this->regionCrumbs($region, false), ['name' => __('public.nav_spots'), 'url' => $this->links->spots($pref)], ['name' => $spot->title, 'url' => null]];
        $ld = ['@context' => 'https://schema.org', '@type' => 'TouristAttraction', 'name' => $spot->title, 'url' => $this->urls->canonicalUrl($this->links->spot($spot))];
        if ($spot->address !== null) {
            $ld['address'] = $spot->address;
        }
        if ($spot->lat !== null && $spot->lng !== null) {
            $ld['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $spot->lat, 'longitude' => (float) $spot->lng];
        }

        return $this->page($spot->title, $region->name.'のスポット。'.($spot->body ?? ''), $this->links->spot($spot), $crumbs, ogType: 'article', extraLd: [$ld]);
    }

    public function article(Article $article): PageMeta
    {
        $region = $article->region()->firstOrFail();
        $pref = $this->links->prefSlug($region);
        $crumbs = [...$this->regionCrumbs($region, false), ['name' => __('public.nav_articles'), 'url' => $this->links->articles($pref)], ['name' => $article->title, 'url' => null]];
        $ld = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'url' => $this->urls->canonicalUrl($this->links->article($article)),
        ];
        if ($article->published_at !== null) {
            $ld['datePublished'] = $article->published_at->toIso8601String();
        }
        $ld['dateModified'] = $article->updated_at?->toIso8601String();

        return $this->page($article->title, $article->body, $this->links->article($article), $crumbs, ogType: 'article', extraLd: [$ld]);
    }

    private function iso(string $date, ?string $time): string
    {
        return $time === null || $time === '' ? $date : $date.'T'.substr($time, 0, 5).':00+09:00';
    }
}
