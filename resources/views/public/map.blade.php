<x-layouts.public :meta="$meta" :pref="$pref" current="map" footer="none">
    <h1 class="visually-hidden">{{ __('public.map_title', ['region' => $region->name]) }}</h1>
    <div class="map-app" data-map-app>
        <section class="map-panel" aria-label="{{ __('public.map_list') }}">
            <div class="map-search"><label class="visually-hidden" for="map-q">{{ __('public.map_search') }}</label><input id="map-q" type="search" placeholder="{{ __('public.map_search') }}" autocomplete="off" data-map-q></div>
            <div class="map-chips" data-map-chips>
                <button type="button" class="map-chip" aria-pressed="true" data-kind="event">{{ __('public.nav_events') }}</button>
                <button type="button" class="map-chip" aria-pressed="true" data-kind="spot">{{ __('public.nav_spots') }}</button>
                <button type="button" class="map-chip" aria-pressed="false" data-weekend>{{ __('public.map_weekend') }}</button>
                @foreach ($categories as $category)<button type="button" class="map-chip" aria-pressed="false" data-category="{{ $category }}">{{ $category }}</button>@endforeach
            </div>
            <p class="map-count t-small" data-map-count data-template="{{ __('public.map_count', ['count' => ':count']) }}" aria-live="polite"></p>
            <ul class="map-list" data-map-list></ul>
            <p class="map-note t-small">{{ __('public.map_night_note') }}</p>
        </section>
        <div class="map map-stage" data-map data-pins='@json($pins, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)' data-lat="{{ $center['lat'] }}" data-lng="{{ $center['lng'] }}" data-zoom="10" data-controls="custom" data-labels='@json(['in' => __('public.map_zoom_in'), 'out' => __('public.map_zoom_out'), 'locate' => __('public.map_locate')])' role="application" aria-label="{{ __('public.nav_map') }}"></div>
    </div>
    <noscript><p>{{ __('public.map_noscript') }}</p></noscript>
</x-layouts.public>