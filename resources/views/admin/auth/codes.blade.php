<x-layouts.admin-auth :step="4">
    <h1 class="t-h1">{{ __('auth.codes_title') }}</h1>
    <p>{{ __('auth.codes_lead') }}</p>
    <ul class="codes" id="recovery-codes">
        @foreach ($codes as $code)
            <li>{{ $code }}</li>
        @endforeach
    </ul>
    <div class="button-row">
        <button type="button" class="btn" data-download="#recovery-codes" data-filename="{{ __('auth.codes_filename') }}">{{ __('auth.codes_download') }}</button>
        <button type="button" class="btn" data-copy="#recovery-codes" data-copied="{{ __('auth.copied') }}">{{ __('auth.codes_copy') }}</button>
    </div>
    <label class="check-row">
        <input type="checkbox" id="codes-saved" data-enables="#codes-continue">
        <span>{{ __('auth.codes_saved') }}</span>
    </label>
    <a id="codes-continue" class="btn btn-primary btn-block" href="{{ url('/admin') }}" aria-disabled="true" tabindex="-1">{{ __('auth.codes_continue') }}</a>
</x-layouts.admin-auth>