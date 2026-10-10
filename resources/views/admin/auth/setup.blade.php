<x-layouts.admin-auth :step="3">
    <h1 class="t-h1">{{ __('auth.setup_title') }}</h1>
    <p>{{ __('auth.setup_lead') }}</p>
    <ol>
        <li>{{ __('auth.setup_step_1') }}</li>
        <li>{{ __('auth.setup_step_2') }}</li>
        <li>{{ __('auth.setup_step_3') }}</li>
    </ol>
    <div class="qr-row">
        <div class="qr" role="img" aria-label="QR">{!! $qr !!}</div>
        <div>
            <p class="t-caption t-muted">{{ __('auth.setup_key_help') }}</p>
            <p class="key" id="totp-key">{{ $key }}</p>
            <button type="button" class="btn btn-sm" data-copy="#totp-key" data-copied="{{ __('auth.copied') }}">{{ __('auth.setup_copy') }}</button>
        </div>
    </div>
    <form method="post" action="{{ route('admin.two-factor.enable') }}">
        @csrf
        <div class="field">
            <label for="code">{{ __('auth.code_label') }}</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="20" required class="code-input" placeholder="000000">
            @error('code')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <x-button type="submit" variant="primary" class="btn-block">{{ __('auth.setup_submit') }}</x-button>
    </form>
</x-layouts.admin-auth>