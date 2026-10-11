@props([
    'title' => null,
    'current' => null,
    'bare' => false,
    'backHref' => null,
    'backLabel' => null,
    'position' => null,
])
<x-layouts.base :title="$title ? $title.' | '.__('layout.admin') : __('layout.admin')" :noindex="true">
    <a class="visually-hidden" href="#main">{{ __('layout.skip_to_main') }}</a>
    @if ($bare)
        <header class="admin-bar-top">
            <a class="admin-back" href="{{ $backHref }}"><span aria-hidden="true">‹</span> {{ $backLabel }}</a>
            @if ($position)<span class="t-muted">{{ $position }}</span>@endif
            <span class="admin-bar-brand"><x-logo :size="20" :href="route('admin.dashboard')" /><span class="admin-badge">{{ __('layout.admin_badge') }}</span></span>
        </header>
        <main id="main" class="admin-bare-main">
            @if (session('status'))<p class="alert alert-success" role="status">{{ session('status') }}</p>@endif
            @if (session('error'))<p class="alert alert-danger" role="alert">{{ session('error') }}</p>@endif
            {{ $slot }}
        </main>
    @else
    <div class="admin-shell">
        <nav class="admin-nav" aria-label="{{ __('layout.admin') }}">
            <div class="admin-brand">
                <x-logo :size="24" :href="route('admin.dashboard')" />
                <span class="admin-badge">{{ __('layout.admin_badge') }}</span>
            </div>
            @php($n = auth()->check() ? app(\App\Services\Admin\DashboardStats::class)->nav() : ['review' => 0, 'corrections' => 0, 'tips' => 0, 'rejected' => 0, 'undecided' => 0])
            <ul>
                <li><a href="{{ route('admin.dashboard') }}" @if ($current === 'dashboard') aria-current="page" @endif>{{ __('layout.admin_dashboard') }}</a></li>
                @can('review')
                    <li><a href="{{ route('admin.review') }}" @if ($current === 'review') aria-current="page" @endif>{{ __('submission.review_title') }}@if ($n['review'] > 0)<span class="nav-count is-alert">{{ $n['review'] }}</span>@endif</a></li>
                    <li><a href="{{ route('admin.tips') }}" @if ($current === 'tips') aria-current="page" @endif>{{ __('layout.nav_tips') }}@if ($n['tips'] > 0)<span class="nav-count is-outline">{{ $n['tips'] }}</span>@endif</a></li>
                    <li><a href="{{ route('admin.corrections') }}" @if ($current === 'corrections') aria-current="page" @endif>{{ __('layout.nav_corrections') }}@if ($n['corrections'] > 0)<span class="nav-count is-outline">{{ $n['corrections'] }}</span>@endif</a></li>
                    <li><a href="{{ route('admin.review.rejected') }}" @if ($current === 'rejected') aria-current="page" @endif>{{ __('layout.nav_rejected') }}<span class="nav-count">{{ $n['rejected'] }}</span></a></li>
                @endcan
                @can('manage-masters')
                    <li><a href="{{ route('admin.inquiries') }}" @if ($current === 'inquiries') aria-current="page" @endif>{{ __('inquiry.admin_title') }}</a></li>
                @endcan
                @can('review')
                    <li class="nav-group">{{ __('layout.nav_content') }}</li>
                    <li><a href="{{ route('admin.events') }}" @if ($current === 'events') aria-current="page" @endif>{{ __('content.events_title') }}@if ($n['undecided'] > 0)<span class="nav-count is-text">{{ __('layout.nav_undecided', ['count' => $n['undecided']]) }}</span>@endif</a></li>
                    <li><a href="{{ route('admin.contents', ['tab' => 'spot']) }}" @if ($current === 'contents' && request('tab', 'spot') === 'spot') aria-current="page" @endif>{{ __('layout.nav_spots') }}</a></li>
                    <li><a href="{{ route('admin.contents', ['tab' => 'article']) }}" @if ($current === 'contents' && request('tab') === 'article') aria-current="page" @endif>{{ __('layout.nav_articles') }}</a></li>
                    <li><a href="{{ route('admin.contents', ['tab' => 'comment']) }}" @if ($current === 'contents' && request('tab') === 'comment') aria-current="page" @endif>{{ __('layout.nav_comments') }}</a></li>
                    <li><a href="{{ route('admin.region-pages') }}" @if ($current === 'region-pages') aria-current="page" @endif>{{ __('region.admin_title') }}</a></li>
                    <li><a href="{{ route('admin.drafts') }}" @if ($current === 'drafts') aria-current="page" @endif>{{ __('ai.draft_title') }}</a></li>
                @endcan
                @can('manage-masters')
                    <li><a href="{{ route('admin.sources') }}" @if ($current === 'sources') aria-current="page" @endif>{{ __('crawl.title') }}</a></li>
                    <li class="nav-group">{{ __('layout.nav_operation') }}</li>
                    <li><a href="{{ route('admin.masters') }}" @if ($current === 'masters') aria-current="page" @endif>{{ __('masters.title') }}</a></li>
                    <li><a href="{{ route('admin.users') }}" @if ($current === 'users') aria-current="page" @endif>{{ __('users.title') }}</a></li>
                @endcan
                @can('manage-settings')
                    <li><a href="{{ route('admin.ads') }}" @if ($current === 'ads') aria-current="page" @endif>{{ __('ads.title') }}</a></li>
                    <li><a href="{{ route('admin.settings') }}" @if ($current === 'settings') aria-current="page" @endif>{{ __('settings.title') }}</a></li>
                    <li><a href="{{ route('admin.logs') }}" @if ($current === 'logs') aria-current="page" @endif>{{ __('logs.title') }}</a></li>
                    <li><a href="{{ route('admin.update') }}" @if ($current === 'update') aria-current="page" @endif>{{ __('admin.update') }}</a></li>
                @endcan
            </ul>
            @auth
                <div class="admin-account">
                    <p class="t-small t-muted" style="margin:0">{{ auth()->user()?->name }}</p>
                    <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="link-button">{{ __('auth.account_logout') }}</button></form>
                    <form method="post" action="{{ route('admin.two-factor.reset') }}" data-confirm="{{ __('auth.account_reset_confirm') }}">@csrf<button type="submit" class="link-button">{{ __('auth.account_reset_two_factor') }}</button></form>
                </div>
            @endauth
        </nav>
        <main id="main" class="admin-main">
            @if (session('status'))
                <p class="alert alert-success" role="status">{{ session('status') }}</p>
            @endif
            @if (session('error'))
                <p class="alert alert-danger" role="alert">{{ session('error') }}</p>
            @endif
            {{ $slot }}
        </main>
    </div>
    @endif
</x-layouts.base>