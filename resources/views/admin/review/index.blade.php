<x-layouts.admin :title="__('submission.review_title')" current="review">
    <div class="page-head">
        <h1 class="t-h1">{{ __('submission.review_title') }}</h1>
        <p class="t-small t-muted">{{ __('submission.review_lead') }}</p>
    </div>
    @include('admin.review._tabs', ['current' => $tab, 'counts' => $counts])
    <div class="tabs">
        <a href="{{ route('admin.review', ['tab' => $tab]) }}" @if ($type === null) aria-current="page" @endif>{{ __('submission.all_types') }}</a>
        @foreach (\App\Enums\SubmissionType::cases() as $t)
            @if ($t !== \App\Enums\SubmissionType::Event)
                <a href="{{ route('admin.review', ['tab' => $tab, 'type' => $t->value]) }}" @if ($type === $t) aria-current="page" @endif>{{ $t->label() }}</a>
            @endif
        @endforeach
    </div>
    <div class="table-wrap" id="ai-live-list" data-ai-live>
        <table class="table">
            <thead><tr><th>{{ __('submission.col_receipt') }}</th><th>{{ __('submission.col_type') }}</th><th>{{ __('submission.col_summary') }}</th><th>{{ __('submission.col_from') }}</th><th>{{ __('submission.col_status') }}</th><th>{{ __('aistatus.title') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($submissions as $s)
                    <tr>
                        <td><code>{{ $s->receipt_no }}</code><br><span class="t-small t-muted">{{ $s->created_at?->format('Y-m-d H:i') }}</span></td>
                        <td>{{ $s->type->label() }}@if ($s->media_count) <span class="t-small t-muted">(写真 {{ $s->media_count }})</span>@endif</td>
                        <td>{{ \Illuminate\Support\Str::limit($s->text('title') ?? $s->text('body') ?? $s->text('source_url') ?? $s->text('proposed_value') ?? '', 60) }}</td>
                        <td>{{ $s->user?->name ?? __('submission.anonymous') }}</td>
                        <td><span class="pill">{{ $s->status->label() }}</span></td>
                        <td><x-ai-badge :status="app(\App\Services\Ai\AiStatusService::class)->forSubmission($s)" /></td>
                        <td class="actions"><a class="btn btn-sm" href="{{ route('admin.review.show', $s) }}">{{ __('submission.open') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $submissions->links() }}
</x-layouts.admin>