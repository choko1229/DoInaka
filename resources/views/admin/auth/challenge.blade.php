<x-layouts.admin-auth :step="2">
    <h1 class="t-h1">{{ __('auth.two_factor_title') }}</h1>
    <p>{{ __('auth.two_factor_lead') }}</p>
    <form method="post" action="{{ route('admin.two-factor.verify') }}">
        @csrf
        <div class="field">
            <label for="code">{{ __('auth.code_label') }}</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="20" required autofocus class="code-input">
            @error('code')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <label class="check-row">
            <input type="checkbox" name="remember" value="1">
            <span>{{ __('auth.remember', ['days' => $days]) }}</span>
        </label>
        <x-button type="submit" variant="primary" class="btn-block">{{ __('auth.confirm') }}</x-button>
    </form>
    <p><a href="{{ route('admin.two-factor.recovery') }}">{{ __('auth.use_recovery') }}</a></p>
</x-layouts.admin-auth>