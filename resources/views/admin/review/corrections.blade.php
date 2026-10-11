<x-layouts.admin :title="__('submission.tab_corrections')" current="corrections">
    <div class="page-head"><h1 class="t-h1">{{ __('submission.tab_corrections') }}</h1><p class="t-small t-muted">{{ __('submission.corrections_private') }}</p></div>

    <h2 class="board-h2">{{ __('submission.auto_applied_title', ['count' => $autoApplied->count()]) }}</h2>
    <p class="t-small t-muted">{{ __('submission.needs_check_lead') }}</p>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('submission.target') }}</th><th>{{ __('submission.col_field') }}</th><th>{{ __('submission.proposed') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($autoApplied as $s)
                    @php($c = $s->corrections->first())
                    <tr>
                        <td><code>{{ $s->receipt_no }}</code><br><span class="t-small t-muted">{{ $c?->target_type }} #{{ $c?->target_id }}</span></td>
                        <td>{{ $c ? __('submission.fields.'.$c->field) : '' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($c?->proposed_value ?? '', 60) }}</td>
                        <td class="actions">
                            <form method="post" action="{{ route('admin.corrections.confirm', $s) }}">@csrf<button class="btn btn-sm btn-primary" type="submit">{{ __('submission.confirm') }}</button></form>
                            <form method="post" action="{{ route('admin.corrections.rollback', $s) }}">@csrf<button class="btn btn-sm" type="submit">{{ __('submission.rollback') }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="board-h2">{{ __('submission.manual_title', ['count' => $submissions->total()]) }}</h2>
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
                        <td class="actions"><a class="btn btn-sm btn-primary" href="{{ route('admin.review.show', $s) }}">{{ __('submission.open') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $submissions->links() }}
    <p class="t-small t-muted">{{ __('submission.corrections_note') }}</p>
</x-layouts.admin>