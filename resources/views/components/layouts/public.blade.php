@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
    'compact' => true,
])
<x-layouts.base :title="$title" :description="$description" :noindex="$noindex">
    <a class="visually-hidden" href="#main">{{ __('layout.skip_to_main') }}</a>
    <x-sky-header :compact="$compact" />
    <main id="main">
        <div class="container">
            {{ $slot }}
        </div>
    </main>
    <x-site-footer />
</x-layouts.base>
