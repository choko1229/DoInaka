<x-layouts.admin-auth :step="1">
    <h1 class="t-h1">{{ __('auth.admin_login_title') }}</h1>
    <p>{{ __('auth.admin_login_lead') }}</p>
    <x-google-button :href="route('auth.google', ['admin' => 1])" class="google-btn-block" />
    @error('google')
        <p class="alert alert-danger" role="alert">{{ $message }}</p>
    @enderror
</x-layouts.admin-auth>