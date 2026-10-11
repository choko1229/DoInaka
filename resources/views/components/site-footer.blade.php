@props(['variant' => 'inner'])
{{-- フッター。トップは「リンク+ひとこと」と「空の色」の切り替え、そのほかのページは「リンク」と「ひとこと」を1行に(画面デザイン) --}}
<footer class="site-footer {{ $variant === 'top' ? 'is-top' : 'is-inner' }}">
    <div class="container">
        <div class="footer-main">
            <nav class="footer-links" aria-label="{{ __('layout.footer_nav') }}">
                <a href="/about/">{{ __('public.about') }}</a>
                <a href="/terms/">{{ __('public.terms') }}</a>
                <a href="/privacy/">{{ __('public.privacy') }}</a>
                <a href="/policy/">{{ __('public.policy') }}</a>
                <a href="/contact/">{{ __('public.contact') }}</a>
                <button type="button" class="link-button" data-cookie-settings>{{ __('public.cookie_settings') }}</button>
            </nav>
            <p class="footer-tagline">{{ __('layout.footer_aside') }}</p>
        </div>
        @if ($variant === 'top')
            <x-theme-switch />
        @endif
    </div>
</footer>