<x-layouts.public :title="__('geo.blocked_title')" :noindex="true">
    <x-empty-state code="403" :title="__('geo.blocked_title')" :aside="__('geo.blocked_aside')">
        {{ __('geo.blocked_body') }}
        <br><span lang="en">{{ __('geo.blocked_en') }}</span>
        <br>
        <a href="/terms/">{{ __('public.terms') }}</a> · <a href="/privacy/">{{ __('public.privacy') }}</a> · <a href="/about/">{{ __('public.about') }}</a> · <a href="/contact/">{{ __('public.contact') }}</a>
    </x-empty-state>
</x-layouts.public>