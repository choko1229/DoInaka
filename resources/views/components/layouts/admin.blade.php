@props([
    'title' => null,
    'current' => null,
])
<x-layouts.base :title="$title ? $title.' | '.__('layout.admin') : __('layout.admin')" :noindex="true">
    <a class="visually-hidden" href="#main">{{ __('layout.skip_to_main') }}</a>
    <div class="admin-shell">
        <nav class="admin-nav" aria-label="{{ __('layout.admin') }}">
            <div class="admin-brand">
                <x-logo :size="24" :href="route('admin.dashboard')" />
                <span class="admin-badge">{{ __('layout.admin_badge') }}</span>
            </div>
            <ul>
                {{-- 項目は各フェーズの画面を作るときに足す --}}
                <li><a href="{{ route('admin.dashboard') }}" @if ($current === 'dashboard') aria-current="page" @endif>{{ __('layout.admin_dashboard') }}</a></li>
                @can('review')
                    <li class="nav-group">{{ __('layout.nav_content') }}</li>
                    <li><a href="{{ route('admin.review') }}" @if ($current === 'review') aria-current="page" @endif>{{ __('submission.review_title') }}</a></li>
                    <li><a href="{{ route('admin.drafts') }}" @if ($current === 'drafts') aria-current="page" @endif>{{ __('ai.draft_title') }}</a></li>
                    <li><a href="{{ route('admin.region-pages') }}" @if ($current === 'region-pages') aria-current="page" @endif>{{ __('region.admin_title') }}</a></li>
                    <li><a href="{{ route('admin.events') }}" @if ($current === 'events') aria-current="page" @endif>{{ __('content.events_title') }}</a></li>
                    <li><a href="{{ route('admin.contents') }}" @if ($current === 'contents') aria-current="page" @endif>{{ __('content.contents_title') }}</a></li>
                @endcan
                @can('manage-masters')
                    <li class="nav-group">{{ __('layout.nav_operation') }}</li>
                    <li><a href="{{ route('admin.sources') }}" @if ($current === 'sources') aria-current="page" @endif>{{ __('crawl.title') }}</a></li>
                    <li><a href="{{ route('admin.users') }}" @if ($current === 'users') aria-current="page" @endif>{{ __('users.title') }}</a></li>
                    <li><a href="{{ route('admin.masters') }}" @if ($current === 'masters') aria-current="page" @endif>{{ __('masters.title') }}</a></li>
                @endcan
                @can('manage-settings')
                    <li><a href="{{ route('admin.settings') }}" @if ($current === 'settings') aria-current="page" @endif>{{ __('settings.title') }}</a></li>
                    <li><a href="{{ route('admin.logs') }}" @if ($current === 'logs') aria-current="page" @endif>{{ __('logs.title') }}</a></li>
                    <li><a href="{{ route('admin.ads') }}" @if ($current === 'ads') aria-current="page" @endif>{{ __('ads.title') }}</a></li>
                    <li><a href="{{ route('admin.update') }}" @if ($current === 'update') aria-current="page" @endif>{{ __('admin.update') }}</a></li>
                @endcan
            </ul>
            @auth
                <div class="admin-account">
                    <p class="t-small t-muted" style="margin:0">{{ auth()->user()?->name }}</p>
                    <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="link-button">{{ __('auth.account_logout') }}</button></form>
                    <form method="post" action="{{ route('admin.two-factor.reset') }}" onsubmit="return confirm(@js(__('auth.account_reset_confirm')))">@csrf<button type="submit" class="link-button">{{ __('auth.account_reset_two_factor') }}</button></form>
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
</x-layouts.base>