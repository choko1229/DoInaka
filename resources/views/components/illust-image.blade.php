@props(['illust', 'variant' => \App\Enums\IllustVariant::Card, 'alt' => '', 'lazy' => true])
@php
    $resolver = app(\App\Services\Design\IllustUrlResolver::class);
    $url = $resolver->url($illust, $variant);
    // カードは 600px と 1200px を srcset で出し分ける(一覧では小さい方で足りる)
    $small = $variant === \App\Enums\IllustVariant::Card ? $resolver->url($illust, \App\Enums\IllustVariant::CardSm) : null;
@endphp
{{-- WebP がないときは何も出さない。親の背景色(surface-sunken)がそのまま見える --}}
@if ($url)
    <img
        class="illust-image"
        src="{{ $url }}"
        @if ($small) srcset="{{ $small }} 600w, {{ $url }} 1200w" sizes="(min-width: 768px) 400px, 100vw" @endif
        width="{{ $variant->width() }}" height="{{ $variant->height() }}"
        alt="{{ $alt }}"
        @if ($lazy) loading="lazy" @endif
        decoding="async"
    >
@endif
