<x-layouts.admin :title="__('admin.dashboard')" current="dashboard">
    <div class="board-head"><h1 class="board-title">{{ __('admin.board_title') }}</h1><span class="t-small t-muted">{{ now()->setTimezone('Asia/Tokyo')->isoFormat('M月D日(dd) H:mm') }}</span></div>
    <x-admin-warnings :warnings="$warnings" />

    @can('review')
        <div class="board-stats">
            <a class="card board-stat" href="{{ route('admin.review') }}"><span class="t-small t-muted">{{ __('admin.stat_review') }}</span><strong class="is-alert">{{ $counts['review'] }}</strong><span class="t-small t-muted">@if ($board['oldest_days'] !== null){{ __('admin.oldest', ['days' => $board['oldest_days']]) }}@else{{ __('admin.none_waiting') }}@endif</span></a>
            <a class="card board-stat" href="{{ route('admin.corrections') }}"><span class="t-small t-muted">{{ __('admin.stat_needs_check') }}</span><strong class="is-warn">{{ $counts['needs_check'] }}</strong><span class="t-small t-muted">{{ __('admin.needs_check_note') }}</span></a>
            <a class="card board-stat" href="{{ route('admin.review.rejected') }}"><span class="t-small t-muted">{{ __('layout.nav_rejected') }}</span><strong>{{ app(\App\Services\Admin\DashboardStats::class)->nav()['rejected'] }}</strong><span class="t-small t-muted">{{ __('admin.rejected_note') }}</span></a>
            <a class="card board-stat" href="{{ route('admin.events') }}"><span class="t-small t-muted">{{ __('admin.stat_undecided') }}</span><strong>{{ app(\App\Services\Admin\DashboardStats::class)->nav()['undecided'] }}</strong><span class="t-small t-muted">{{ __('admin.undecided_note') }}</span></a>
        </div>
    @endcan

    <div class="board-two">
        <section class="card board-card">
            <h2>{{ __('admin.ai_today_title') }}</h2>
            <p class="board-line"><span>{{ __('admin.ai_used') }}</span><strong>{{ __('admin.times', ['count' => $aiToday]) }}</strong></p>
            <p class="board-line board-status"><span class="board-dot {{ $aiPausedUntil !== null ? 'is-warn' : 'is-ok' }}" aria-hidden="true"></span>{{ $aiPausedUntil !== null ? __('admin.ai_paused') : __('admin.ai_usable') }}</p>
            <p class="t-small t-muted">{{ __('admin.ai_limit_note') }}</p>
        </section>
        <section class="card board-card">
            <h2>{{ __('admin.server_title') }}</h2>
            <p class="board-line"><span>{{ __('admin.server_cron') }}</span><strong class="{{ $cron['lastRun'] !== null && $cron['lastRun']->diffInMinutes(now()) < 10 ? 'is-ok' : 'is-bad' }}">@if ($cron['lastRun'] !== null && $cron['lastRun']->diffInMinutes(now()) < 10){{ __('admin.server_running', ['minutes' => max(1, (int) $cron['lastRun']->diffInMinutes(now()))]) }}@else{{ __('admin.server_stopped') }}@endif</strong></p>
            <p class="board-line"><span>{{ __('admin.server_queue') }}</span><strong>{{ __('admin.items', ['count' => $board['queue']]) }}</strong></p>
            <p class="board-line"><span>{{ __('admin.server_failed') }}</span><strong class="{{ $board['failed'] > 0 ? 'is-bad' : '' }}">{{ __('admin.items', ['count' => $board['failed']]) }}</strong></p>
        </section>
    </div>

    @can('review')
        <div class="board-head"><h2 class="board-h2">{{ __('admin.stat_review') }}</h2><a href="{{ route('admin.review') }}">{{ __('public.see_all') }}</a></div>
        <div class="card board-table">
            <table>
                <thead><tr><th>{{ __('submission.col_type') }}</th><th>{{ __('submission.col_summary') }}</th><th>{{ __('admin.col_poster') }}</th><th>{{ __('admin.col_ai') }}</th><th>{{ __('admin.col_received') }}</th></tr></thead>
                <tbody>
                    @forelse ($board['pending'] as $s)
                        <tr>
                            <td class="t-muted">{{ $s->type->label() }}</td>
                            <td><a href="{{ route('admin.review.show', $s) }}">{{ \Illuminate\Support\Str::limit($s->text('title') ?? $s->text('body') ?? $s->text('source_url') ?? $s->text('proposed_value') ?? $s->receipt_no, 40) }}</a></td>
                            <td>{{ $s->user?->name ?? __('public.anonymous') }}@if ($s->user) <span class="t-small t-muted">({{ __('admin.record', ['count' => $s->user->approved_count]) }})</span>@endif</td>
                            <td>@if ($s->ai_score !== null)<span class="status-tag is-published">{{ __('admin.safe', ['score' => number_format((float) $s->ai_score, 2)]) }}</span>@elseif ($s->ai_status !== null)<span class="status-tag">{{ $s->ai_status }}</span>@else<span class="t-small t-muted">—</span>@endif</td>
                            <td class="t-muted">{{ $s->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h2 class="board-h2">{{ __('admin.auto_title') }}</h2>
        <div class="card board-table">
            <ul class="board-auto">
                @forelse ($board['auto'] as $s)
                    <li>
                        <span @class(['status-tag', 'is-published' => $s->auto_decision === 'approved', 'is-rejected' => $s->auto_decision !== 'approved'])>{{ $s->auto_decision === 'approved' ? __('admin.auto_approved') : __('admin.auto_rejected') }}</span>
                        <span>{{ \Illuminate\Support\Str::limit($s->text('title') ?? $s->text('body') ?? $s->receipt_no, 50) }}</span>
                        @if ($s->ai_score !== null)<span class="t-small t-muted">{{ __('admin.safe', ['score' => number_format((float) $s->ai_score, 2)]) }}</span>@endif
                        <a class="board-auto-link" href="{{ route('admin.review.show', $s) }}">{{ __('admin.look') }}</a>
                    </li>
                @empty
                    <li class="t-muted">{{ __('admin.auto_none') }}</li>
                @endforelse
            </ul>
        </div>
    @endcan

    <details class="board-more">
        <summary>{{ __('admin.more_numbers') }}</summary>
        <h2 class="t-h2" style="margin-top:var(--space-4)">{{ __('admin.stats_todo') }}</h2>    <div class="card-grid stat-grid">
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

    @include('admin.partials.ai-status', ['ai' => $ai])

    <section class="card">
        <div class="card-head"><h2 class="t-h2">{{ __('cron.title') }}</h2>@can('manage-settings')<a href="{{ route('admin.settings', ['tab' => 'cron']) }}">{{ __('cron.settings_link') }}</a>@endcan</div>
        <p><strong>{{ $cron['mode']->label() }}</strong> — {{ __('cron.mode_help.'.$cron['mode']->value) }}</p>
        <p class="t-small t-muted">{{ __('cron.last_run') }}: {{ $cron['lastRun']?->setTimezone('Asia/Tokyo')->format('Y-m-d H:i:s') ?? __('cron.never') }}</p>
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
    </details>
</x-layouts.admin>