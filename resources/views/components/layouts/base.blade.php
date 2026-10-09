@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
])
@php
    $siteName = config('app.name');
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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
</body>
</html>
