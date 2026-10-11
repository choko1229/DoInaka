@props(['compact' => true, 'nav' => [], 'cta' => null])
{{-- 空のヘッダー(画面デザイン: ロゴ + ナビ + 「投稿する」のボタン。スマホはメニュー) --}}
<header class="sky-header">
    <div class="container">
        <x-logo />
        @if ($nav !== [] || $cta)
            <nav class="sky-header-nav" aria-label="{{ __('layout.main_nav') }}">
                @foreach ($nav as $item)
                    <a href="{{ $item['href'] }}" @if (! empty($item['current'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
                @if ($cta)<a class="sky-cta" href="{{ $cta['href'] }}">{{ $cta['label'] }}</a>@endif
            </nav>
            <details class="sky-header-menu">
                <summary aria-label="{{ __('layout.menu') }}">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </summary>
                <nav class="sky-header-drawer" aria-label="{{ __('layout.main_nav') }}">
                    @foreach ($nav as $item)
                        <a href="{{ $item['href'] }}" @if (! empty($item['current'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @endforeach
                    @if ($cta)<a class="sky-cta" href="{{ $cta['href'] }}">{{ $cta['label'] }}</a>@endif
                </nav>
            </details>
        @endif
    </div>
    <svg class="sky-header-hills" viewBox="0 0 1280 56" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 34 Q260 6 560 28 T1280 24 V56 H0Z" fill="currentColor"/>
        <path class="ground" d="M0 46 Q640 38 1280 46 V56 H0Z"/>
    </svg>
    <svg class="sky-header-glow" viewBox="0 0 1280 72" aria-hidden="true" style="position:absolute;inset:0;width:100%;height:72px;pointer-events:none">
        <circle cx="200" cy="68" r="2"/><circle cx="640" cy="22" r="1.5"/><circle cx="899" cy="64" r="2"/><circle cx="1099" cy="74" r="2"/>
    </svg>
</header>