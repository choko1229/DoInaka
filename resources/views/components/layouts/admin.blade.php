@props([
    'title' => null,
    'current' => null,
])
<x-layouts.base :title="$title ? $title.' | '.__('layout.admin') : __('layout.admin')" :noindex="true">
    <a class="visually-hidden" href="#main">{{ __('layout.skip_to_main') }}</a>
    <div class="admin-shell">
        <nav class="admin-nav" aria-label="{{ __('layout.admin') }}">
            <x-logo size="24" />
            <span class="admin-badge">{{ __('layout.admin') }}</span>
            <ul>
                {{-- 項目は各フェーズの画面を作るときに足す --}}
                <li><a href="{{ url('/admin') }}" @if ($current === 'dashboard') aria-current="page" @endif>{{ __('layout.admin_dashboard') }}</a></li>
            </ul>
        </nav>
        <main id="main" class="admin-main">
            {{ $slot }}
        </main>
    </div>
</x-layouts.base>
