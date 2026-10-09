@php($counts = ['review' => \App\Models\Submission::query()->where('status', \App\Enums\SubmissionStatus::InReview)->count(), 'waiting' => 0, 'rejected' => \App\Models\Submission::query()->whereIn('status', [\App\Enums\SubmissionStatus::Rejected, \App\Enums\SubmissionStatus::AutoRejected])->count()])
<x-layouts.admin :title="__('submission.tab_corrections')" current="review">
    <div class="page-head"><h1 class="t-h1">{{ __('submission.tab_corrections') }}</h1></div>
    @include('admin.review._tabs', ['current' => 'corrections', 'counts' => $counts])
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('submission.col_receipt') }}</th><th>{{ __('submission.target') }}</th><th>{{ __('submission.col_field') }}</th><th>{{ __('submission.now') }}</th><th>{{ __('submission.proposed') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($submissions as $s)
                    @php($c = $s->corrections->first())
                    <tr>
                        <td><code>{{ $s->receipt_no }}</code></td>
                        <td>{{ $c?->target_type }} #{{ $c?->target_id }}</td>
                        <td>{{ $c ? __('submission.fields.'.$c->field) : '' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($current[$s->id] ?? '', 40) }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($c?->proposed_value ?? '', 40) }}@if ($c?->source_url)<br><span class="t-small t-muted">{{ __('submission.has_source') }}</span>@endif</td>
                        <td class="actions"><a class="btn btn-sm" href="{{ route('admin.review.show', $s) }}">{{ __('submission.open') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $submissions->links() }}
</x-layouts.admin>