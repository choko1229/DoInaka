@props(['step' => null])
<x-layouts.base :title="__('auth.admin_login_title')" :noindex="true">
    <main id="main" class="admin-auth">
        @if ($step !== null && $step > 1)
            <ol class="auth-steps" aria-label="{{ __('auth.two_factor_title') }}">
                @foreach ([1 => 'step_login', 2 => 'step_code', 3 => 'step_setup', 4 => 'step_recovery'] as $number => $label)
                    <li @if ($number === $step) aria-current="step" @endif>{{ __('auth.'.$label) }}</li>
                @endforeach
            </ol>
        @endif
        <div class="admin-auth-brand">
            <x-logo :size="28" />
            <span class="admin-badge">{{ __('auth.admin_badge') }}</span>
        </div>
        <section class="card admin-auth-card">
            {{ $slot }}
        </section>
        <p class="t-caption t-muted admin-auth-foot">{!! __('auth.admin_footer', ['link' => '<a href="'.e(url('/')).'">'.e(__('auth.admin_footer_link')).'</a>']) !!}</p>
    </main>
</x-layouts.base>