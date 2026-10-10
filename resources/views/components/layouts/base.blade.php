@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
    'meta' => null,
])
@php
    $siteName = config('app.name');
    if ($meta instanceof \App\Support\PageMeta) {
        $title = $meta->title;
        $description = $meta->description;
        $noindex = $meta->noindex;
    }
    $pageTitle = $title ? $title.' | '.$siteName : $siteName;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $themeContext->theme->value }}" data-season="{{ $themeContext->season->value }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @if ($noindex)
        <meta name="robots" content="noindex, follow">
    @endif
    @if ($meta instanceof \App\Support\PageMeta)
        @if ($meta->canonical)
            <link rel="canonical" href="{{ $meta->canonical }}">
            <meta property="og:url" content="{{ $meta->canonical }}">
        @endif
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:type" content="{{ $meta->ogType }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        @if ($description)<meta property="og:description" content="{{ $description }}">@endif
        @if ($meta->image)<meta property="og:image" content="{{ $meta->image }}">@endif
        <meta name="twitter:card" content="{{ $meta->image ? 'summary_large_image' : 'summary' }}">
        @foreach ($meta->jsonLd as $block)
            {{-- JSON を <script> に入れるので、</script> や <!-- で抜け出せないようにエスケープする --}}
            <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        @endforeach
    @endif
    @php
        // 設定を読めないとき(DB の障害・設置前)でも、ページは描く
        try {
            $ga4 = app(\App\Services\Setting\SettingsService::class)->string(\App\Enums\SettingKey::AnalyticsGa4Id);
        } catch (\Throwable) {
            $ga4 = '';
        }
    @endphp
    @if (preg_match('/^G-[A-Z0-9]+$/', $ga4) === 1)
        <meta name="ga4-id" content="{{ $ga4 }}">
    @endif
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
    @unless (request()->is('admin', 'admin/*', 'install', 'install/*'))
        <x-cookie-banner />
    @endunless
</body>
</html>