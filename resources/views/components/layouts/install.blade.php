@props(['step' => 1])
@php
    $steps = [1 => 'step_key', 2 => 'step_database', 3 => 'step_site', 4 => 'step_done'];
@endphp
<x-layouts.base :title="__('install.title')" :noindex="true">
    <x-sky-header />
    <main id="main">
        <div class="container container-narrow">
            <h1 class="t-h1">{{ __('install.title') }}</h1>
            <ol class="stepper" aria-label="{{ __('install.title') }}">
                @foreach ($steps as $number => $label)
                    <li @if ($number === $step) aria-current="step" @endif>{{ $number }}. {{ __('install.'.$label) }}</li>
                @endforeach
            </ol>
            {{ $slot }}
        </div>
    </main>
</x-layouts.base>