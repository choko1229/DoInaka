@php($counts = ['review' => \App\Models\Submission::query()->where('status', \App\Enums\SubmissionStatus::InReview)->count(), 'waiting' => 0, 'rejected' => \App\Models\Submission::query()->whereIn('status', [\App\Enums\SubmissionStatus::Rejected, \App\Enums\SubmissionStatus::AutoRejected])->count()])
<x-layouts.admin :title="__('submission.tab_tips')" current="review">
    <div class="page-head"><h1 class="t-h1">{{ __('submission.tab_tips') }}</h1><p class="t-small t-muted">{{ __('submission.tips_lead') }}</p></div>
    @include('admin.review._tabs', ['current' => 'tips', 'counts' => $counts])
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('submission.col_receipt') }}</th><th>{{ __('submission.tip_url') }}</th><th>{{ __('submission.photos') }}</th><th>{{ __('submission.col_status') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($tips as $tip)
                    <tr>
                        <td><code>{{ $tip->receipt_no }}</code><br><span class="t-small t-muted">{{ $tip->created_at?->format('Y-m-d H:i') }}</span></td>
                        <td>{{ $tip->text('source_url') ?? '—' }}@if (($tip->payload['inspection']['status'] ?? null) === 'robots_blocked')<br><span class="pill pill-warning">{{ __('submission.inspection_robots_blocked') }}</span>@endif</td>
                        <td>{{ $tip->media_count }}</td>
                        <td><span class="pill">{{ $tip->status->label() }}</span></td>
                        <td class="actions"><a class="btn btn-sm" href="{{ route('admin.tips.show', $tip) }}">{{ __('submission.open') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $tips->links() }}
</x-layouts.admin>