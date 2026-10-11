<x-layouts.public :title="__('auth.login_title')" :noindex="true" :focus="true">
    <div class="login-layout">
        <section class="card login-card">
            <h1 class="t-h1">{{ __('auth.login_title') }}</h1>
            <p>{{ __('auth.login_lead') }}</p>

            @error('google')
                <p class="alert alert-danger" role="alert">{{ $message }}</p>
            @enderror

            <x-google-button :href="route('auth.google')" class="google-btn-block" />

            <div class="notes">
                <p class="t-strong" style="margin:0">{{ __('auth.login_benefits_title') }}</p>
                <ul class="t-small">
                    <li>{{ __('auth.login_benefit_1') }}</li>
                    <li>{{ __('auth.login_benefit_2') }}</li>
                    <li>{{ __('auth.login_benefit_3') }}</li>
                </ul>
            </div>

            <p class="t-small t-muted">{!! __('auth.login_consent', [
                'terms' => '<a href="'.e(url('/terms/')).'">'.e(__('auth.terms')).'</a>',
                'privacy' => '<a href="'.e(url('/privacy/')).'">'.e(__('auth.privacy')).'</a>',
            ]) !!}</p>
        </section>

        <div class="login-art" aria-hidden="true">
            <svg viewBox="0 0 340 220" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="64" cy="48" r="16"/><path d="M40 190 H320"/><path d="M52 190 C110 130 190 138 250 128 C268 126 280 122 300 118"/>
                <path d="M200 130 V90 L232 66 L264 90 V130 Z"/><path d="M222 130 V104 H242 V130"/>
            </svg>
            <p class="t-aside">{{ __('auth.login_aside') }}</p>
        </div>
    </div>
</x-layouts.public>