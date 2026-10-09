<x-layouts.admin :title="__('admin.dashboard')" current="dashboard">
    <h1 class="t-h1">{{ __('admin.dashboard') }}</h1>
    <x-admin-warnings :warnings="$warnings" />
    <p class="t-muted">{{ __('admin.dashboard_lead') }}</p>
</x-layouts.admin>