@php($current = $themeContext->preference->value)
<div class="theme-switch" role="group" aria-label="{{ __('layout.theme_switch') }}">
    @foreach (['auto' => 'theme_auto', 'day' => 'theme_day', 'night' => 'theme_night'] as $value => $label)
        <button type="button" data-theme-option="{{ $value }}" aria-pressed="{{ $current === $value ? 'true' : 'false' }}">{{ __('layout.'.$label) }}</button>
    @endforeach
</div>
