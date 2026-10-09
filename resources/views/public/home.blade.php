<x-layouts.public :meta="$meta" :compact="false" :pref="$pref">
    <section class="hero">
        <h1 class="t-display">{{ __('layout.tagline') }}</h1>
        <p class="t-aside">{{ __('layout.footer_aside') }}</p>
        <form class="hero-search" action="/{{ $pref }}/events/" method="get" role="search">
            <label class="visually-hidden" for="hero-q">{{ __('public.search_label') }}</label>
            <input id="hero-q" name="q" type="search" maxlength="100" placeholder="{{ __('public.search_placeholder') }}">
            <button class="btn btn-primary" type="submit">{{ __('public.search') }}</button>
        </form>
        <p class="hero-chips">
            <a class="chip" href="/{{ $pref }}/events/weekend/">{{ __('public.weekend') }}</a>
            <a class="chip" href="/{{ $pref }}/events/?when=today">{{ __('public.today') }}</a>
            <a class="chip" href="/{{ $pref }}/map/">{{ __('public.nav_map') }}</a>
        </p>
    </section>

    @if ($weekend !== [])
        <section class="block">
            <div class="block-head"><h2 class="t-h1">{{ __('public.this_weekend') }}</h2><a href="/{{ $pref }}/events/weekend/">{{ __('public.see_all') }}</a></div>
            <div class="card-grid">@foreach ($weekend as $event)<x-content-card :item="$event" />@endforeach</div>
        </section>
    @endif

    <section class="block">
        <div class="block-head"><h2 class="t-h1">{{ __('public.upcoming') }}</h2><a href="/{{ $pref }}/events/">{{ __('public.see_all') }}</a></div>
        @if ($upcoming === [])
            <p class="t-muted">{{ __('public.no_events') }}</p>
        @else
            <div class="card-grid">@foreach ($upcoming as $event)<x-content-card :item="$event" />@endforeach</div>
        @endif
    </section>

    @if ($spots !== [])
        <section class="block">
            <div class="block-head"><h2 class="t-h1">{{ __('public.popular_spots') }}</h2><a href="/{{ $pref }}/spots/">{{ __('public.see_all') }}</a></div>
            <div class="card-grid">@foreach ($spots as $spot)<x-content-card :item="$spot" />@endforeach</div>
        </section>
    @endif

    <section class="block">
        <h2 class="t-h1">{{ __('public.regions') }}</h2>
        <p class="hero-chips">
            @foreach ($prefectures as $p)
                <a class="chip" href="{{ $links->region($p) }}">{{ $p->name }}</a>
            @endforeach
        </p>
    </section>
</x-layouts.public>