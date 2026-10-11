<x-layouts.admin :title="__('region.admin_title')" current="region-pages">
    <div class="page-head"><h1 class="t-h1">{{ __('region.admin_title') }}</h1><p class="t-small t-muted">{{ __('region.admin_lead') }}</p></div>

    <div class="board-stats">
        <div class="card board-stat"><span class="t-small t-muted">{{ __('region.stat_ready') }}</span><strong>{{ __('region.pages', ['count' => $counts['ready']]) }}</strong><span class="t-small t-muted">{{ __('region.stat_ready_note') }}</span></div>
        <div class="card board-stat"><span class="t-small t-muted">{{ __('region.stat_pending') }}</span><strong>{{ __('region.pages', ['count' => $counts['pending']]) }}</strong><span class="t-small t-muted">{{ __('region.stat_pending_note') }}</span></div>
        <div class="card board-stat"><span class="t-small t-muted">{{ __('region.stat_failed') }}</span><strong>{{ __('region.pages', ['count' => $counts['failed']]) }}</strong><span class="t-small t-muted">{{ __('region.stat_failed_note') }}</span></div>
        <div class="card board-stat"><span class="t-small t-muted">{{ __('region.stat_holds') }}</span><strong>{{ __('admin.items', ['count' => $holds->count()]) }}</strong><span class="t-small t-muted">{{ __('region.stat_holds_note') }}</span></div>
    </div>

    <form method="get" action="{{ route('admin.region-pages') }}" class="filter-bar">
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('region.search') }}" aria-label="{{ __('region.search') }}">
        @foreach ([null => 'all', 'none' => 'none', 'pending' => 'pending', 'ready' => 'ready', 'failed' => 'failed'] as $value => $name)
            <a class="chip" href="{{ route('admin.region-pages', array_filter(['state' => $value, 'q' => $q ?: null])) }}" @if ($filter === $value) aria-current="page" @endif>{{ __('region.filter_'.$name) }}@if ($name === 'pending') {{ $counts['pending'] }}@elseif ($name === 'failed') {{ $counts['failed'] }}@elseif ($name === 'ready') {{ $counts['ready'] }}@endif</a>
        @endforeach
    </form>

    <form method="post" action="{{ route('admin.region-pages.regenerate') }}">
        @csrf
        <div class="table-wrap" id="ai-live-list" data-ai-live>
            <table class="table">
                <thead><tr><th></th><th>{{ __('region.col_region') }}</th><th>{{ __('region.col_state') }}</th><th>{{ __('aistatus.title') }}</th><th>{{ __('region.col_search') }}</th><th>{{ __('region.col_checked') }}</th><th>{{ __('region.col_hits') }}</th></tr></thead>
                <tbody>
                    @forelse ($regions as $region)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $region->id }}" aria-label="{{ $region->name }}"></td>
                            <td><a href="/{{ $region->path() }}/" target="_blank" rel="noopener">{{ $region->name }}</a></td>
                            <td>
                                @if ($region->intro_body)<span class="pill pill-success">{{ __('region.state_ready') }}</span>
                                @elseif (in_array($region->queue_status, ['pending', 'running'], true))<span class="pill pill-warning">{{ __('region.state_pending') }}{{ $region->queue_priority == 1 ? ' ('.__('region.first').')' : '' }}</span>
                                @elseif ($region->queue_status === 'failed')<span class="pill pill-failed">{{ __('region.state_failed') }}</span> <span class="t-small t-muted">{{ $region->queue_error }}</span>
                                @else<span class="pill">{{ __('region.state_none') }}</span>@endif
                            </td>
                            <td><x-ai-badge :status="app(\App\Services\Ai\AiStatusService::class)->forRegion($region, $region->queue_status, $region->queue_error)" /></td>
                            <td><span class="status-tag {{ $region->intro_body && $region->intro_fact_checked ? 'is-ok' : 'is-ended' }}">{{ $region->intro_body && $region->intro_fact_checked ? __('region.search_public') : __('region.search_hidden') }}</span></td>
                            <td>{{ $region->intro_generated_at?->setTimezone('Asia/Tokyo')->isoFormat('M/D') ?? '—' }}@if ($region->intro_generated_at) {{ __('region.checked_cites', ['count' => is_array($region->intro_sources) ? count($region->intro_sources) : 0]) }}@endif</td>
                            <td>{{ $region->queue_hits ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p><button class="btn btn-primary" type="submit">{{ __('region.regenerate_selected') }}</button></p>
    </form>
    {{ $regions->links() }}

    <section class="card board-card"><h2>{{ __('region.regen_title') }}</h2><p class="t-muted">{{ __('region.regen_body') }}</p></section>

    <h2 class="board-h2">{{ __('region.holds_title', ['count' => $holds->count()]) }}</h2>
    @forelse ($holds as $hold)
        <section class="card board-card hold-row">
            <div><span class="t-small t-muted">{{ $hold->user?->name ?? __('submission.anonymous') }}・{{ $hold->created_at?->diffForHumans() }}</span><br><strong>{{ \Illuminate\Support\Str::limit($hold->corrections->first()?->proposed_value ?? $hold->receipt_no, 80) }}</strong></div>
            <a class="btn btn-sm btn-primary" href="{{ route('admin.review.show', $hold) }}">{{ __('submission.open') }}</a>
        </section>
    @empty
        <p class="t-muted">{{ __('submission.review_empty') }}</p>
    @endforelse
</x-layouts.admin>