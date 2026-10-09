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
                <li><a href="{{ route('admin.update') }}" @if ($current === 'update') aria-current="page" @endif>{{ __('admin.update') }}</a></li>
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