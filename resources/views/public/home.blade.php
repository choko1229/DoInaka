@php
    $illust = app(\App\Services\Design\IllustSelector::class)->select('home', [$pref], $themeContext->theme, $themeContext->season, now());
    $illustUrls = app(\App\Services\Design\IllustUrlResolver::class);
    $wide = $illustUrls->url($illust, \App\Enums\IllustVariant::Wide);
    $card = $illustUrls->url($illust, \App\Enums\IllustVariant::Card);
    $heroStyle = ($card ? '--hero-image: url('.$card.');' : '').($wide ? '--hero-image-wide: url('.$wide.');' : '');
    $seasonKey = $themeContext->season->value;
@endphp
<x-layouts.public :meta="$meta" :compact="false" :pref="$pref">
    <x-slot:hero>
        <header class="hero-header" style="{{ $heroStyle }}">
            <div class="container">
                <div class="hero-top">
                    <x-logo :size="32" />
                    @php($heroNav = [
                        ['href' => "/{$pref}/events/", 'label' => __('public.nav_events')],
                        ['href' => "/{$pref}/spots/", 'label' => __('public.nav_spots')],
                        ['href' => "/{$pref}/articles/", 'label' => __('public.nav_articles')],
                        ['href' => "/{$pref}/map/", 'label' => __('public.nav_map')],
                        auth()->check() ? ['href' => '/mypage/', 'label' => __('public.nav_mypage')] : ['href' => '/login', 'label' => __('public.nav_login')],
                    ])
                    <nav class="sky-header-nav" aria-label="{{ __('layout.main_nav') }}">
                        @foreach ($heroNav as $item)<a href="{{ $item['href'] }}">{{ $item['label'] }}</a>@endforeach
                        <a class="sky-cta" href="/post/">{{ __('public.nav_post') }}</a>
                    </nav>
                    <details class="sky-header-menu">
                        <summary aria-label="{{ __('layout.menu') }}"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></summary>
                        <nav class="sky-header-drawer" aria-label="{{ __('layout.main_nav') }}">
                            @foreach ($heroNav as $item)<a href="{{ $item['href'] }}">{{ $item['label'] }}</a>@endforeach
                            <a class="sky-cta" href="/post/">{{ __('public.nav_post') }}</a>
                        </nav>
                    </details>
                </div>
                <div class="hero-copy">
                    <h1 class="hero-title">{{ __('layout.tagline') }}</h1>
                    <p class="hero-lead">{{ __('public.hero_lead') }}<span class="hero-time">{{ __('public.hero_time.'.$themeContext->theme->value) }}</span></p>
                    <form class="hero-pc-search" action="/{{ $pref }}/events/" method="get" role="search">
                        <label class="visually-hidden" for="hero-q-pc">{{ __('public.search_label') }}</label>
                        <input id="hero-q-pc" name="q" type="search" maxlength="100" placeholder="{{ __('public.hero_search_placeholder') }}">
                        <button type="submit">{{ __('public.search_button') }}</button>
                    </form>
                    <div class="chip-row hero-pc-chips">
                        <a class="chip-sm" href="/{{ $pref }}/events/?when=today">{{ __('public.today') }}</a>
                        <a class="chip-sm" href="/{{ $pref }}/events/weekend/" aria-current="true">{{ __('public.weekend') }}</a>
                        <a class="chip-sm" href="/{{ $pref }}/events/?near=1">{{ __('public.chip_nearby') }}</a>
                    </div>
                </div>
            </div>
            <svg class="sky-header-hills" viewBox="0 0 390 80" preserveAspectRatio="none" aria-hidden="true"><path d="M0 64 Q195 56 390 64 V80 H0Z"/></svg>
        </header>
    </x-slot:hero>

    <div class="top-main">
        <form class="hero-sp-search" action="/{{ $pref }}/events/" method="get" role="search">
            <label for="hero-q">{{ __('public.search_label') }}</label>
            <div class="row">
                <input id="hero-q" name="q" type="search" maxlength="100" placeholder="{{ __('public.hero_search_placeholder') }}">
                <button class="go" type="submit" aria-label="{{ __('public.search_aria') }}"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg></button>
            </div>
            <div class="chip-row">
                <a class="chip-sm" href="/{{ $pref }}/events/?when=today">{{ __('public.today') }}</a>
                <a class="chip-sm" href="/{{ $pref }}/events/weekend/" aria-current="true">{{ __('public.weekend') }}</a>
                <a class="chip-sm" href="/{{ $pref }}/events/?near=1">{{ __('public.chip_nearby') }}</a>
                <a class="chip-sm" href="/{{ $pref }}/map/">{{ __('public.nav_map') }}</a>
            </div>
        </form>

        <x-ad position="top" />

        {{-- 今週末のイベント。今週末にない間は、これからのイベントを出す(画面デザインは、今週末のものがある前提) --}}
        @php($featured = $weekend !== [] ? $weekend : $upcoming)
        <section class="top-section">
            <div class="top-section-head">
                <h2>{{ $weekend !== [] ? __('public.this_weekend') : __('public.upcoming') }}</h2>
                <a href="/{{ $pref }}/events/{{ $weekend !== [] ? 'weekend/' : '' }}">{{ __('public.see_all') }}</a>
            </div>
            @if ($featured === [])
                <p class="t-muted">{{ __('public.no_events') }}</p>
            @else
                <div class="card-row">@foreach ($featured as $event)<x-content-card :item="$event" />@endforeach</div>
            @endif
        </section>

        <div class="top-two">
            <section class="season-card" aria-labelledby="season-picks">
                <header><span class="season-badge">{{ __('public.season_short.'.$seasonKey) }}</span><h2 id="season-picks">{{ __('public.season_picks') }}</h2></header>
                <ul>
                    @foreach ($seasonPicks as $pick)
                        <li><a href="/{{ $pref }}/events/?q={{ urlencode($pick['q']) }}"><span>{{ $pick['label'] }}</span><span>{{ $pick['count'] }}{{ __('public.count_unit') }}</span></a></li>
                    @endforeach
                </ul>
            </section>
            <section class="top-section" aria-labelledby="areas">
                <h2 id="areas" class="top-areas-title">{{ __('public.areas_title') }}</h2>
                <div class="area-grid">
                    @foreach ($areas['items'] as $area)
                        <a class="area-card" href="{{ $area['href'] }}"><b>{{ $area['name'] }}</b><span>{{ __('public.area_events', ['count' => $area['count']]) }}</span></a>
                    @endforeach
                    @if ($areas['total'] > 0)
                        <a class="area-card" href="/{{ $pref }}/"><b>{{ __('public.area_all') }}</b><span>{{ __('public.area_all_sub', ['count' => $areas['total']]) }}</span></a>
                    @endif
                </div>
            </section>
        </div>

        @if ($spots !== [])
            <section class="top-section">
                <div class="top-section-head"><h2>{{ __('public.popular_spots') }}</h2><a href="/{{ $pref }}/spots/">{{ __('public.see_all') }}</a></div>
                <div class="spot-list">
                    @foreach ($spots as $spot)
                        @php($photo = $spot->media->first())
                        <a class="spot-row" href="{{ $links->for($spot) }}">
                            <span class="spot-row-thumb">@if ($photo)<img src="{{ \App\Support\MediaUrl::small($photo) }}" alt="" loading="lazy">@else<x-illust-image :illust="app(\App\Services\Design\IllustSelector::class)->select('spot'.$spot->id, array_values(array_filter([$spot->category?->slug, $spot->region?->slug])), $themeContext->theme, $themeContext->season, now())" :variant="\App\Enums\IllustVariant::CardSm" />@endif</span>
                            <span><b>{{ $spot->title }}</b><small>{{ $spot->category?->name }}@if ($spot->category && $spot->region) / @endif{{ $spot->region?->name }}</small></span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($articles !== [])
            <section class="top-section">
                <div class="top-section-head"><h2>{{ __('public.new_articles') }}</h2><a href="/{{ $pref }}/articles/">{{ __('public.see_all') }}</a></div>
                <div class="spot-list">
                    @foreach ($articles as $article)
                        @php($photo = $article->media->first())
                        <a class="spot-row" href="{{ $links->for($article) }}">
                            <span class="spot-row-thumb">@if ($photo)<img src="{{ \App\Support\MediaUrl::small($photo) }}" alt="" loading="lazy">@else<x-illust-image :illust="app(\App\Services\Design\IllustSelector::class)->select('article'.$article->id, array_values(array_filter([$article->region?->slug])), $themeContext->theme, $themeContext->season, now())" :variant="\App\Enums\IllustVariant::CardSm" />@endif</span>
                            <span><b>{{ $article->title }}</b><small>{{ $article->region?->name }}</small></span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="cta-band">
            <div><h2>{{ __('public.cta_title') }}</h2><p>{{ __('public.cta_body') }}</p></div>
            <a class="btn btn-primary" href="/post/">{{ __('public.nav_post') }}</a>
        </section>
    </div>
</x-layouts.public>