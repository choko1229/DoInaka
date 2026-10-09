<x-layouts.public :meta="$meta" :pref="$pref" current="map">
    <h1 class="t-h1">{{ __('public.map_title', ['region' => $region->name]) }}</h1>
    <p class="t-small t-muted">{{ __('public.map_legend') }}</p>
    <div class="map map-large" data-map data-pins='@json($pins, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)' data-lat="{{ $center['lat'] }}" data-lng="{{ $center['lng'] }}" data-zoom="10" role="application" aria-label="{{ __('public.nav_map') }}"></div>
    <noscript><p>{{ __('public.map_noscript') }}</p></noscript>
</x-layouts.public>