@props(['item'])
@php
    $links = app(\App\Services\Url\PublicLinks::class);
    $isEvent = $item instanceof \App\Models\Event;
    $hints = array_values(array_filter([$item->category?->slug ?? null, $item->region?->slug ?? null]));
    $illust = app(\App\Services\Design\IllustSelector::class)->select($item->getMorphClass().$item->id, $hints, $themeContext->theme, $themeContext->season, now());
    $status = $isEvent ? $item->displayStatus() : 'scheduled';
    $tags = $item->tags->take(3)->pluck('name')->all();
    // 承認された写真があれば、それをカードに出す(なければイラストと「写真募集中」)
    $firstPhoto = $item->media->first();
    $photoUrl = $firstPhoto ? \App\Support\MediaUrl::small($firstPhoto) : null;
    $photoAlt = $firstPhoto ? ($firstPhoto->alt ?: $item->title) : '';
@endphp
<x-event-card
    :href="$links->for($item)"
    :title="$item->title"
    :date="$isEvent ? \App\Support\DateText::range($item) : null"
    :area="$item->region?->name"
    :tags="$tags"
    :status="$status"
    :photo="$photoUrl"
    :alt="$photoAlt"
    :illust="$photoUrl ? null : $illust"
/>