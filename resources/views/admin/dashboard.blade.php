<x-layouts.admin :title="__('admin.dashboard')" current="dashboard">
    <h1 class="t-h1">{{ __('admin.dashboard') }}</h1>
    <x-admin-warnings :warnings="$warnings" />

    <h2 class="t-h2" style="margin-top:var(--space-6)">{{ __('admin.stats_todo') }}</h2>
    <div class="card-grid stat-grid">
        @can('review')
            <a class="card stat" href="{{ route('admin.review') }}"><span class="t-small t-muted">{{ __('admin.stat_review') }}</span><strong class="t-display">{{ $counts['review'] }}</strong></a>
            <a class="card stat" href="{{ route('admin.corrections') }}"><span class="t-small t-muted">{{ __('admin.stat_corrections') }}</span><strong class="t-display">{{ $counts['corrections'] }}</strong></a>
            <a class="card stat" href="{{ route('admin.tips') }}"><span class="t-small t-muted">{{ __('admin.stat_tips') }}</span><strong class="t-display">{{ $counts['tips'] }}</strong></a>
            <a class="card stat" href="{{ route('admin.review', ['tab' => 'waiting']) }}"><span class="t-small t-muted">{{ __('admin.stat_ai_waiting') }}</span><strong class="t-display">{{ $counts['ai_waiting'] }}</strong></a>
            <a class="card stat" href="{{ route('admin.corrections') }}"><span class="t-small t-muted">{{ __('admin.stat_needs_check') }}</span><strong class="t-display">{{ $counts['needs_check'] }}</strong></a>
        @endcan
    </div>

    <h2 class="t-h2" style="margin-top:var(--space-6)">{{ __('admin.stats_site') }}</h2>
    <div class="card-grid stat-grid">
        <div class="card stat"><span class="t-small t-muted">{{ __('admin.stat_events') }}</span><strong class="t-display">{{ $counts['events_upcoming'] }}</strong></div>
        <div class="card stat"><span class="t-small t-muted">{{ __('admin.stat_spots') }}</span><strong class="t-display">{{ $counts['spots'] }}</strong></div>
        <div class="card stat"><span class="t-small t-muted">{{ __('admin.stat_articles') }}</span><strong class="t-display">{{ $counts['articles'] }}</strong></div>
        <div class="card stat"><span class="t-small t-muted">{{ __('admin.stat_views_today') }}</span><strong class="t-display">{{ $counts['views_today'] }}</strong></div>
        <div class="card stat"><span class="t-small t-muted">{{ __('admin.stat_views_week') }}</span><strong class="t-display">{{ $counts['views_week'] }}</strong></div>
        @can('manage-masters')
            <a class="card stat" href="{{ route('admin.users') }}"><span class="t-small t-muted">{{ __('admin.stat_users') }}</span><strong class="t-display">{{ $counts['users'] }}</strong><span class="t-small t-muted">{{ __('admin.stat_users_suspended', ['count' => $counts['users_suspended']]) }}</span></a>
            <a class="card stat" href="{{ route('admin.sources') }}"><span class="t-small t-muted">{{ __('admin.stat_sources_paused') }}</span><strong class="t-display">{{ $counts['sources_paused'] }}</strong></a>
            <a class="card stat" href="{{ route('admin.region-pages', ['state' => 'pending']) }}"><span class="t-small t-muted">{{ __('admin.stat_region_queue') }}</span><strong class="t-display">{{ $counts['region_queue'] }}</strong></a>
        @endcan
    </div>

    <section class="card">
        <h2 class="t-h2">{{ __('ai.usage_title') }}</h2>
        <p>{{ __('ai.usage_today', ['count' => $aiToday]) }}</p>
        @if ($aiPausedUntil)<p class="alert alert-warning" role="status">{{ __('ai.paused', ['time' => $aiPausedUntil->setTimezone('Asia/Tokyo')->format('m/d H:i')]) }}</p>@endif
    </section>

    @can('manage-settings')
        <section class="card">
            <div class="card-head"><h2 class="t-h2">{{ __('admin.recent_ops') }}</h2><a href="{{ route('admin.logs') }}">{{ __('admin.all_logs') }}</a></div>
            <ul class="t-small">
                @forelse ($recent as $log)
                    <li>{{ $log->created_at?->setTimezone('Asia/Tokyo')->format('m/d H:i') }} — <code>{{ $log->action->value }}</code> {{ $log->target_type }} {{ $log->target_id }}</li>
                @empty
                    <li class="t-muted">{{ __('submission.review_empty') }}</li>
                @endforelse
            </ul>
        </section>
    @endcan
</x-layouts.admin>