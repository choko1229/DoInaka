<x-layouts.admin :title="__('submission.tab_tips')" current="tips">
    <div class="page-head"><h1 class="t-h1">{{ __('submission.tab_tips') }}</h1><p class="t-small t-muted">{{ __('submission.tips_lead') }}</p></div>

    <div class="board-stats">
        <div class="card board-stat"><span class="t-small t-muted">{{ __('submission.tips_waiting') }}</span><strong>{{ __('admin.items', ['count' => $counts['waiting']]) }}</strong><span class="t-small t-muted">{{ __('submission.tips_kinds', ['url' => $counts['url'], 'photo' => $counts['photo']]) }}</span></div>
        <div class="card board-stat"><span class="t-small t-muted">{{ __('submission.tips_reading') }}</span><strong>{{ __('admin.items', ['count' => $counts['reading']]) }}</strong><span class="t-small t-muted">{{ __('submission.tips_reading_note') }}</span></div>
        <div class="card board-stat"><span class="t-small t-muted">{{ __('submission.tips_published') }}</span><strong>{{ __('admin.items', ['count' => $counts['published']]) }}</strong><span class="t-small t-muted">{{ __('submission.tips_published_note') }}</span></div>
        <div class="card board-stat"><span class="t-small t-muted">{{ __('submission.tips_dismissed') }}</span><strong>{{ __('admin.items', ['count' => $counts['dismissed']]) }}</strong><span class="t-small t-muted">{{ __('submission.tips_month') }}</span></div>
    </div>

    <nav class="filter-bar" aria-label="{{ __('submission.tab_tips') }}">
        @foreach (['all' => $counts['waiting'], 'url' => $counts['url'], 'photo' => $counts['photo']] as $key => $n)
            <a class="chip" href="{{ route('admin.tips', array_filter(['kind' => $key === 'all' ? null : $key])) }}" @if ($kind === $key) aria-current="page" @endif>{{ __('submission.tips_filter_'.$key) }} {{ $n }}</a>
        @endforeach
    </nav>

    <div class="tips-grid">
        <ul class="tips-list" id="ai-live-list" data-ai-live>
            @forelse ($tips as $tip)
                <li>
                    <a href="{{ route('admin.tips', array_filter(['id' => $tip->id, 'kind' => $kind === 'all' ? null : $kind])) }}" @class(['is-selected' => $selected?->id === $tip->id])>
                        <span class="tips-kind t-small t-muted">{{ $tip->text('source_url') ? __('submission.tips_filter_url') : __('submission.tips_filter_photo') }}</span>
                        <x-ai-badge :status="app(\App\Services\Ai\AiStatusService::class)->forSubmission($tip)" />
                        <strong>{{ \Illuminate\Support\Str::limit($tip->text('source_url') ? (parse_url((string) $tip->text('source_url'), PHP_URL_HOST) ?: $tip->text('source_url')) : $tip->receipt_no, 36) }}</strong>
                        <span class="t-small t-muted">{{ $tip->created_at?->setTimezone('Asia/Tokyo')->isoFormat('M/D H:mm') }}・{{ $tip->user?->name ?? __('submission.anonymous') }}</span>
                        @if (($tip->payload['inspection']['status'] ?? null) === 'robots_blocked')<span class="pill pill-warning">{{ __('submission.inspection_robots_blocked') }}</span>@endif
                    </a>
                </li>
            @empty
                <li class="t-muted tips-empty">{{ __('submission.review_empty') }}</li>
            @endforelse
        </ul>
        <div class="tips-detail">
            @if ($selected)
                @php($submission = $selected)
                @include('admin.tips.partials.detail', ['inspection' => $selected->payload['inspection'] ?? null])
            @endif
        </div>
    </div>
    {{ $tips->links() }}
    <p class="t-small t-muted">{{ __('submission.tips_footer') }}</p>
</x-layouts.admin>