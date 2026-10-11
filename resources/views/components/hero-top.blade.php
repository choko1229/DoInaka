@props(['pref'])
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
