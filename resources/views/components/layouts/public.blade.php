@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
    'compact' => true,
    'meta' => null,
    'pref' => null,
    'current' => null,
    'hero' => null,
    'footer' => 'inner',
    'focus' => false,
])
@php
    $navPref = $pref ?? request()->route('pref');
    $navPref = is_string($navPref) && preg_match('/^[a-z0-9-]+$/', $navPref) === 1 ? $navPref : 'kagawa';
    $nav = [
        ['href' => "/{$navPref}/events/", 'label' => __('public.nav_events'), 'current' => $current === 'events'],
        ['href' => "/{$navPref}/spots/", 'label' => __('public.nav_spots'), 'current' => $current === 'spots'],
        ['href' => "/{$navPref}/articles/", 'label' => __('public.nav_articles'), 'current' => $current === 'articles'],
        ['href' => "/{$navPref}/map/", 'label' => __('public.nav_map'), 'current' => $current === 'map'],
        // ログイン中は「マイページ」、そうでなければ「ログイン」(画面デザインどおり)
        auth()->check()
            ? ['href' => '/mypage/', 'label' => __('public.nav_mypage'), 'current' => $current === 'mypage']
            : ['href' => '/login', 'label' => __('public.nav_login'), 'current' => $current === 'login'],
    ];
    $cta = ['href' => '/post/', 'label' => __('public.nav_post')];
    $isAdminBarCandidate = auth()->check() && auth()->user()->isStaff();
@endphp
<x-layouts.base :title="$title" :description="$description" :noindex="$noindex" :meta="$meta">
    <a class="visually-hidden" href="#main">{{ __('layout.skip_to_main') }}</a>
    {{-- 管理者バーの置き場。HTML には管理者の情報を入れず、表示後に /admin/bar を読み込んで差し込む(設計書6.5) --}}
    @if ($isAdminBarCandidate)
        <div id="admin-bar" data-url="{{ url('/admin/bar') }}" data-page="{{ request()->getPathInfo() }}"></div>
    @endif
    @if ($hero)
        {{ $hero }}
    @else
        <x-sky-header :compact="$compact" :nav="$focus ? [] : $nav" :cta="$focus ? null : $cta" />
    @endif
    <main id="main" @class(['is-hero' => $hero, 'is-bare' => $footer === 'none' && ! $focus, 'is-focus' => $focus])>
        <div class="container">
            @if ($meta instanceof \App\Support\PageMeta && $meta->breadcrumbs !== [])
                <x-breadcrumbs :items="$meta->breadcrumbs" />
            @endif
            {{ $slot }}
        </div>
    </main>
    @if ($footer !== 'none' && ! $focus)<x-site-footer :variant="$hero ? 'top' : $footer" />@endif
</x-layouts.base>