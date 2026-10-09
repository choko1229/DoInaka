@props(['item'])
@php
    $links = app(\App\Services\Url\PublicLinks::class);
    $isEvent = $item instanceof \App\Models\Event;
    $hints = array_values(array_filter([$item->category?->slug ?? null, $item->region?->slug ?? null]));
    $illust = app(\App\Services\Design\IllustSelector::class)->select($item->getMorphClass().$item->id, $hints, $themeContext->theme, $themeContext->season, now());
    $status = $isEvent ? $item->displayStatus() : 'scheduled';
    $tags = $item->tags->take(3)->pluck('name')->all();
@endphp
<x-event-card
    :href="$links->for($item)"
    :title="$item->title"
    :date="$isEvent ? \App\Support\DateText::range($item) : null"
    :area="$item->region?->name"
    :tags="$tags"
    :status="$status"
    :illust="$illust"
/>