@php($counts = ['review' => \App\Models\Submission::query()->where('status', \App\Enums\SubmissionStatus::InReview)->count(), 'waiting' => 0, 'rejected' => $submissions->total()])
<x-layouts.admin :title="__('submission.tab_rejected')" current="review">
    <div class="page-head"><h1 class="t-h1">{{ __('submission.tab_rejected') }}</h1><p class="t-small t-muted">{{ __('submission.rejected_lead') }}</p></div>
    @include('admin.review._tabs', ['current' => 'rejected', 'counts' => $counts])
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('submission.col_receipt') }}</th><th>{{ __('submission.col_type') }}</th><th>{{ __('submission.col_summary') }}</th><th>{{ __('submission.col_status') }}</th><th>{{ __('submission.days_left') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($submissions as $s)
                    <tr>
                        <td><code>{{ $s->receipt_no }}</code></td>
                        <td>{{ $s->type->label() }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($s->text('title') ?? $s->text('body') ?? $s->text('source_url') ?? '', 50) }}<br><span class="t-small t-muted">{{ $s->reject_reason }}</span></td>
                        <td><span class="pill">{{ $s->status->label() }}</span></td>
                        <td>{{ $s->expires_at ? max(0, (int) ceil(now()->diffInDays($s->expires_at, false))) : '—' }}</td>
                        <td class="actions">
                            <a class="btn btn-sm" href="{{ route('admin.review.show', $s) }}">{{ __('submission.open') }}</a>
                            <form method="post" action="{{ route('admin.review.restore', $s) }}" style="display:inline">@csrf<button class="btn btn-sm" type="submit">{{ __('submission.restore') }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $submissions->links() }}
</x-layouts.admin>