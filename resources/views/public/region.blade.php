@php
    $links = app(\App\Services\Url\PublicLinks::class);
@endphp
<x-layouts.public :meta="$meta" :pref="$pref" :compact="! $isPref">
    @php
        $resolver = app(\App\Services\Design\IllustUrlResolver::class);
        $illust = new \App\Data\Illust($isPref ? \App\Enums\Place::Island : \App\Enums\Place::Field, $themeContext->season, $themeContext->theme);
        $wide = $resolver->url($illust, \App\Enums\IllustVariant::Wide);
        $card = $resolver->url($illust, \App\Enums\IllustVariant::Card);
    @endphp
    {{-- 県は島と海、地域は田園のイラスト(PC は横長、スマホは4:3。docs/illustrations.md) --}}
    @if ($wide && $card)
        <picture class="region-art" aria-hidden="true">
            <source media="(min-width: 768px)" srcset="{{ $wide }}">
            <img src="{{ $card }}" alt="" width="1200" height="900" decoding="async">
        </picture>
    @endif
    <header class="region-head">
        <h1 class="t-display">{{ $region->name }}</h1>
        @if ($region->name_kana)<p class="t-small t-muted">{{ $region->name_kana }}</p>@endif
        @if ($region->era)
            <p class="alert">{{ $region->era->label() }}@if ($region->abolished_on)({{ __('public.abolished', ['date' => $region->abolished_on->format('Y年n月j日')]) }})@endif @if ($region->merged_into){{ __('public.merged_into', ['name' => $region->merged_into]) }}@endif</p>
        @endif
    </header>

    <section class="card">
        <h2 class="t-h2">{{ __('public.region_intro') }}</h2>
        @if ($region->intro_body)
            <p>{!! nl2br(e($region->intro_body)) !!}</p>
            @if ($region->intro_generated_at)
                <p class="t-small t-muted">{{ __('region.ai_notice') }} {{ __('region.last_checked', ['date' => $region->intro_generated_at->format('Y-m-d')]) }}</p>
            @endif
            <p class="t-small"><a href="/report/region/{{ $region->id }}/">{{ __('public.report_error') }}</a></p>
            @if (is_array($region->intro_sources) && $region->intro_sources !== [])
                <p class="t-small t-muted">{{ __('public.region_sources') }}:
                    @foreach ($region->intro_sources as $source)
                        @php($srcUrl = is_array($source) ? (string) ($source['url'] ?? '') : (string) $source)
                        @if (preg_match('#^https?://#i', $srcUrl) === 1)<a href="{{ $srcUrl }}" rel="nofollow noopener" target="_blank">{{ is_array($source) ? ($source['title'] ?? $srcUrl) : $srcUrl }}</a>@endif
                    @endforeach
                </p>
            @endif
        @else
            <p class="t-muted">{{ __('public.region_preparing') }}</p>
        @endif
    </section>

    @if ($children->isNotEmpty() || $alsoChildren->isNotEmpty() || $former->isNotEmpty())
        <section class="block">
            <h2 class="t-h1">{{ $isPref ? __('public.cities') : __('public.areas') }}</h2>
            <p class="hero-chips">
                @foreach ($children as $child)<a class="chip" href="{{ $links->region($child) }}">{{ $child->name }}</a>@endforeach
            </p>
            @if ($alsoChildren->isNotEmpty())
                <h3 class="t-h3">{{ __('public.also_part') }}</h3>
                <p class="hero-chips">@foreach ($alsoChildren as $child)<a class="chip" href="{{ $links->region($child) }}">{{ $child->name }}</a>@endforeach</p>
            @endif
            @if ($former->isNotEmpty())
                <h3 class="t-h3">{{ __('public.former_towns') }}</h3>
                <p class="hero-chips">@foreach ($former as $child)<a class="chip" href="{{ $links->region($child) }}">{{ $child->name }}</a>@endforeach</p>
            @endif
        </section>
    @endif

    <section class="block">
        <div class="block-head"><h2 class="t-h1">{{ __('public.region_events', ['region' => $region->name]) }}</h2><a href="/{{ $pref }}/events/">{{ __('public.see_all') }}</a></div>
        @if ($events->isEmpty())<p class="t-muted">{{ __('public.no_events') }}</p>
        @else<div class="card-grid">@foreach ($events as $event)<x-content-card :item="$event" />@endforeach</div>@endif
    </section>

    @if ($spots->isNotEmpty())
        <section class="block">
            <div class="block-head"><h2 class="t-h1">{{ __('public.region_spots', ['region' => $region->name]) }}</h2><a href="/{{ $pref }}/spots/">{{ __('public.see_all') }}</a></div>
            <div class="card-grid">@foreach ($spots as $spot)<x-content-card :item="$spot" />@endforeach</div>
        </section>
    @endif
</x-layouts.public>