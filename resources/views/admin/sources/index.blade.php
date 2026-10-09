<x-layouts.admin :title="__('crawl.title')" current="sources">
    <div class="page-head">
        <h1 class="t-h1">{{ __('crawl.title') }}</h1>
        <x-button :href="route('admin.sources.create')" variant="primary">{{ __('crawl.add') }}</x-button>
    </div>
    <p class="t-small t-muted">{{ __('crawl.lead') }}</p>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('crawl.col_name') }}</th><th>{{ __('crawl.col_state') }}</th><th>{{ __('crawl.col_interval') }}</th><th>{{ __('crawl.col_last') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($sources as $s)
                    <tr>
                        <td><strong>{{ $s->name }}</strong><br><span class="t-small t-muted">{{ $s->url }} · {{ $s->region?->name }}</span></td>
                        <td>
                            @if ($s->isPaused())<span class="pill pill-failed">{{ __('crawl.state_paused') }}</span>
                            @elseif (! $s->is_active)<span class="pill">{{ __('crawl.state_inactive') }}</span>
                            @else<span class="pill pill-success">{{ __('crawl.state_active') }}</span>@endif
                            @if ($s->is_trusted)<span class="pill pill-success">{{ __('crawl.trusted') }}</span>@endif
                            @if ($s->trustProposed())<span class="pill pill-warning">{{ __('crawl.trust_proposed', ['count' => $s->clean_approvals]) }}</span>@endif
                        </td>
                        <td>{{ __('crawl.every_days', ['days' => $s->interval_days]) }}<br><span class="t-small t-muted">{{ __('crawl.next') }}: {{ $s->next_run_at?->format('m/d H:i') ?? '—' }}</span></td>
                        <td>{{ $s->last_run_at?->format('m/d H:i') ?? '—' }}<br><span class="t-small t-muted">{{ __('crawl.failures', ['count' => $s->failure_streak]) }}</span></td>
                        <td class="actions">
                            <form method="post" action="{{ route('admin.sources.run', $s) }}" style="display:inline">@csrf<button class="btn btn-sm" type="submit">{{ __('crawl.run_now') }}</button></form>
                            @if ($s->isPaused())
                                <form method="post" action="{{ route('admin.sources.resume', $s) }}" style="display:inline">@csrf<button class="btn btn-sm" type="submit">{{ __('crawl.resume') }}</button></form>
                            @else
                                <form method="post" action="{{ route('admin.sources.pause', $s) }}" style="display:inline">@csrf<button class="btn btn-sm" type="submit">{{ __('crawl.pause') }}</button></form>
                            @endif
                            <form method="post" action="{{ route('admin.sources.trust', $s) }}" style="display:inline">@csrf<button class="btn btn-sm" type="submit">{{ $s->is_trusted ? __('crawl.trust_off') : __('crawl.trust_on') }}</button></form>
                            <a class="btn btn-sm" href="{{ route('admin.sources.edit', $s) }}">{{ __('content.edit') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="t-h2" style="margin-top:var(--space-8)">{{ __('crawl.candidates') }}</h2>
    <p class="t-small t-muted">{{ __('crawl.candidates_lead') }}</p>
    <div class="table-wrap">
        <table class="table">
            <tbody>
                @forelse ($candidates as $c)
                    <tr>
                        <td>{{ $c->url }}<br><span class="t-small t-muted">{{ $c->host }}</span></td>
                        <td class="actions">
                            <a class="btn btn-sm" href="{{ route('admin.sources.create', ['candidate' => $c->id]) }}">{{ __('crawl.register') }}</a>
                            <form method="post" action="{{ route('admin.sources.candidates.ignore', $c) }}" style="display:inline">@csrf<button class="btn btn-sm" type="submit">{{ __('crawl.ignore') }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="t-h2" style="margin-top:var(--space-8)">{{ __('crawl.runs') }}</h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('crawl.col_name') }}</th><th>{{ __('crawl.col_state') }}</th><th>{{ __('crawl.run_pages') }}</th><th>{{ __('crawl.run_events') }}</th><th>{{ __('crawl.col_last') }}</th></tr></thead>
            <tbody>
                @forelse ($runs as $r)
                    <tr>
                        <td>{{ $r->source?->name }}</td>
                        <td><span class="pill">{{ __('crawl.run_status.'.$r->status) }}</span> <span class="t-small t-muted">{{ $r->error }}</span></td>
                        <td>{{ $r->pages_fetched }} / {{ __('crawl.run_changed', ['count' => $r->pages_changed]) }}</td>
                        <td>{{ $r->events_found }} / {{ __('crawl.run_created', ['count' => $r->submissions_created, 'published' => $r->auto_published]) }}</td>
                        <td>{{ $r->started_at?->format('m/d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>