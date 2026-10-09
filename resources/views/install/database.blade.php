<x-layouts.install :step="2">
    <section class="card">
        <h2 class="t-h2">{{ __('install.db_title') }}</h2>
        <p class="t-small t-muted">{{ __('install.db_help') }}</p>

        @if ($results !== [])
            <x-check-list :results="$results" />
        @endif

        <form method="post" action="{{ route('install.database.save') }}">
            @csrf
            <div class="field">
                <label for="host">{{ __('install.db_host') }}</label>
                <input id="host" name="host" type="text" value="{{ old('host', $values['host']) }}" required autocomplete="off">
                @error('host')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="port">{{ __('install.db_port') }}</label>
                <input id="port" name="port" type="number" min="1" max="65535" value="{{ old('port', $values['port']) }}" required>
                @error('port')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="database">{{ __('install.db_database') }}</label>
                <input id="database" name="database" type="text" value="{{ old('database', $values['database']) }}" required autocomplete="off">
                @error('database')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="username">{{ __('install.db_username') }}</label>
                <input id="username" name="username" type="text" value="{{ old('username', $values['username']) }}" required autocomplete="off">
                @error('username')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="password">{{ __('install.db_password') }}</label>
                <input id="password" name="password" type="password" autocomplete="off">
                @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <x-button type="submit" variant="primary">{{ __('install.db_check') }}</x-button>
        </form>
    </section>
</x-layouts.install>