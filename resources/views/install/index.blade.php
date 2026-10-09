<x-layouts.install :step="1">
    <p>{{ __('install.lead') }}</p>

    <section class="card">
        <h2 class="t-h2">{{ __('install.environment') }}</h2>
        <x-check-list :results="$results" />
        @unless ($canContinue)
            <p class="alert alert-danger" role="alert">{{ __('install.environment_failed') }}</p>
        @endunless
    </section>

    @if ($canContinue)
        <section class="card">
            <h2 class="t-h2">{{ __('install.key_title') }}</h2>
            <p class="t-small t-muted">{{ __('install.key_help', ['path' => $keyPath]) }}</p>
            <form method="post" action="{{ route('install.verify') }}">
                @csrf
                <div class="field">
                    <label for="install_key">{{ __('install.key_label') }}</label>
                    <input id="install_key" name="install_key" type="text" autocomplete="off" required spellcheck="false">
                    @error('install_key')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <x-button type="submit" variant="primary">{{ __('install.next') }}</x-button>
            </form>
        </section>
    @endif
</x-layouts.install>