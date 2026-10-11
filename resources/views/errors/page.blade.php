@php($status = (int) ($status ?? 500))
<x-layouts.public :title="__('errors.'.$status.'.title')" :noindex="true" :focus="true">
    <x-empty-state
        :code="(string) $status"
        :title="__('errors.'.$status.'.title')"
        :aside="__('errors.'.$status.'.aside')"
        :error-id="$status === 500 ? app(\App\Support\ErrorId::class)->current() : null"
    >{{ __('errors.'.$status.'.body') }}</x-empty-state>
</x-layouts.public>