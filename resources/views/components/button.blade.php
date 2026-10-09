@props([
    'variant' => 'secondary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])
@php
    $classes = 'btn'.($variant === 'primary' ? ' btn-primary' : '').($size === 'sm' ? ' btn-sm' : '');
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
