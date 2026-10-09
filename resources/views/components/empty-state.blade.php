@props([
    'code' => null,
    'title',
    'aside' => null,
    'errorId' => null,
])
<section class="empty-state">
    <div class="empty-state-art" aria-hidden="true">
        <svg viewBox="0 0 340 220" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="84" cy="64" r="16"/>
            <rect x="148" y="30" width="122" height="64" rx="8"/>
            <path d="M166 52 H252 M166 70 H226 M209 94 V190"/>
            <path d="M20 190 H340"/>
            <path d="M52 190 C110 130 190 138 250 128 C268 126 280 122 290 118"/>
        </svg>
    </div>
    <div>
        @if ($code)<p class="empty-state-code t-small" style="margin:0">{{ $code }}</p>@endif
        <h1 class="t-display">{{ $title }}</h1>
        @if ($aside)<p class="t-aside" style="margin:var(--space-2) 0 0">{{ $aside }}</p>@endif
        <p class="t-small t-muted" style="margin:var(--space-3) 0 0">{{ $slot }}</p>
        @if ($errorId)
            <p class="empty-state-id t-small t-muted">{{ __('errors.error_id') }}: <code>{{ $errorId }}</code></p>
        @endif
        <div class="empty-state-actions">
            <x-button variant="primary" :href="url('/')">{{ __('errors.back_to_top') }}</x-button>
        </div>
    </div>
</section>
