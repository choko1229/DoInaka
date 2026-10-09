<x-layouts.install :step="3">
    <form method="post" action="{{ route('install.site.save') }}" class="card">
        @csrf
        <h2 class="t-h2">{{ __('install.site_title') }}</h2>

        <div class="field">
            <label for="site_name">{{ __('install.site_name') }}</label>
            <input id="site_name" name="site_name" type="text" value="{{ old('site_name', 'ド田舎.net') }}" required>
            @error('site_name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="site_description">{{ __('install.site_description') }}</label>
            <input id="site_description" name="site_description" type="text" value="{{ old('site_description') }}">
            @error('site_description')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="admin_name">{{ __('install.admin_name') }}</label>
            <input id="admin_name" name="admin_name" type="text" value="{{ old('admin_name') }}" required>
            @error('admin_name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="admin_email">{{ __('install.admin_email') }}</label>
            <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required autocomplete="off">
            <p class="t-small t-muted">{{ __('install.admin_email_help') }}</p>
            @error('admin_email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <h3 class="t-h3">{{ __('install.google_title') }}</h3>
        <div class="field">
            <label for="google_client_id">{{ __('install.google_client_id') }}</label>
            <input id="google_client_id" name="google_client_id" type="text" value="{{ old('google_client_id') }}" autocomplete="off">
            @error('google_client_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="google_client_secret">{{ __('install.google_client_secret') }}</label>
            <input id="google_client_secret" name="google_client_secret" type="password" autocomplete="off">
            @error('google_client_secret')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <x-button type="submit" variant="primary">{{ __('install.finish') }}</x-button>
    </form>
</x-layouts.install>