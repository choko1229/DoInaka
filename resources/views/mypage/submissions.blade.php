<x-layouts.public :meta="$meta">
    <h1 class="t-h1">{{ __('mypage.nav_submissions') }}</h1>
    <x-mypage-nav current="submissions" />
    <p class="t-small t-muted">{{ __('mypage.submissions_lead') }}</p>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('submission.col_receipt') }}</th><th>{{ __('submission.col_type') }}</th><th>{{ __('submission.col_summary') }}</th><th>{{ __('submission.col_status') }}</th></tr></thead>
            <tbody>
                @forelse ($submissions as $s)
                    <tr>
                        <td><code>{{ $s->receipt_no }}</code><br><span class="t-small t-muted">{{ $s->created_at?->format('Y-m-d') }}</span></td>
                        <td>{{ $s->type->label() }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($s->text('title') ?? $s->text('body') ?? $s->text('source_url') ?? $s->text('proposed_value') ?? '', 50) }}</td>
                        <td>
                            <span class="pill">{{ $s->status->label() }}</span>
                            @if ($s->status === \App\Enums\SubmissionStatus::Rejected && $s->reject_reason)<br><span class="t-small t-muted">{{ $s->reject_reason }}</span>@endif
                            @if ($s->status === \App\Enums\SubmissionStatus::Approved && in_array($s->target_type, ['event', 'spot', 'article'], true) && $s->target_id)
                                @php($published = match ($s->target_type) { 'event' => \App\Models\Event::query()->find($s->target_id), 'spot' => \App\Models\Spot::query()->find($s->target_id), default => \App\Models\Article::query()->find($s->target_id) })
                                @if ($published && $published->is_published)<br><a class="t-small" href="{{ $links->for($published) }}">{{ __('mypage.view_published') }}</a>@endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="t-muted">{{ __('mypage.empty_submissions') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $submissions->links() }}
</x-layouts.public>