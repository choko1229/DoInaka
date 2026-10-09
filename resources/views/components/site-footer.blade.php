<footer class="site-footer">
    <div class="container">
        <div>
            <nav class="footer-links" aria-label="{{ __('layout.footer_nav') }}">
                <a href="/terms/">{{ __('public.terms') }}</a>
                <a href="/privacy/">{{ __('public.privacy') }}</a>
                <a href="/policy/">{{ __('public.policy') }}</a>
                <a href="/about/">{{ __('public.about') }}</a>
                <a href="/contact/">{{ __('public.contact') }}</a>
                <button type="button" class="link-button" data-cookie-settings>{{ __('public.cookie_settings') }}</button>
            </nav>
            <p class="t-aside t-muted" style="margin:0">{{ __('layout.footer_aside') }}</p>
        </div>
        <x-theme-switch />
    </div>
</footer>
