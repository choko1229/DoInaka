@props(['compact' => true, 'nav' => []])
<header class="sky-header">
    <div class="container">
        <x-logo />
        @if ($nav !== [])
            <nav class="sky-header-nav" aria-label="{{ __('layout.main_nav') }}">
                @foreach ($nav as $item)
                    <a href="{{ $item['href'] }}" @if (! empty($item['current'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
            </nav>
        @endif
    </div>
    <svg class="sky-header-hills" viewBox="0 0 1280 28" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 18 C160 2 320 4 520 14 C720 24 900 6 1100 10 C1190 12 1240 16 1280 14 V28 H0 Z" fill="currentColor"/>
        <path class="ground" d="M0 24 C200 14 420 16 640 22 C860 28 1060 18 1280 22 V28 H0 Z"/>
    </svg>
    <svg class="sky-header-glow" viewBox="0 0 1280 72" aria-hidden="true" style="position:absolute;inset:0;width:100%;height:72px;pointer-events:none">
        <circle cx="200" cy="68" r="2"/><circle cx="640" cy="22" r="1.5"/><circle cx="899" cy="64" r="2"/><circle cx="1099" cy="74" r="2"/>
    </svg>
</header>
