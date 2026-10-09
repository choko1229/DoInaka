<x-layouts.admin-auth :step="2">
    <h1 class="t-h1">{{ __('auth.recovery_page_title') }}</h1>
    <p>{{ __('auth.recovery_page_lead') }}</p>
    <form method="post" action="{{ route('admin.two-factor.recovery.verify') }}">
        @csrf
        <div class="field">
            <label for="recovery_code">{{ __('auth.recovery_label') }}</label>
            <input id="recovery_code" name="recovery_code" type="text" autocomplete="off" maxlength="30" required autofocus class="code-input" spellcheck="false">
            @error('recovery_code')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <label class="check-row">
            <input type="checkbox" name="remember" value="1">
            <span>{{ __('auth.remember', ['days' => app(\App\Services\Auth\TrustedDevices::class)->days()]) }}</span>
        </label>
        <x-button type="submit" variant="primary" class="btn-block">{{ __('auth.confirm') }}</x-button>
    </form>
    <p><a href="{{ route('admin.two-factor') }}">{{ __('auth.recovery_back') }}</a></p>
</x-layouts.admin-auth>