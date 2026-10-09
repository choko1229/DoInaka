<x-layouts.admin :title="__('admin.dashboard')" current="dashboard">
    <h1 class="t-h1">{{ __('admin.dashboard') }}</h1>
    <x-admin-warnings :warnings="$warnings" />
    <section class="card">
        <h2 class="t-h2">{{ __('ai.usage_title') }}</h2>
        <p>{{ __('ai.usage_today', ['count' => $aiToday]) }}</p>
        @if ($aiPausedUntil)<p class="alert alert-warning" role="status">{{ __('ai.paused', ['time' => $aiPausedUntil->setTimezone('Asia/Tokyo')->format('m/d H:i')]) }}</p>@endif
    </section>
    <p class="t-muted">{{ __('admin.dashboard_lead') }}</p>
</x-layouts.admin>