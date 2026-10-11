<x-layouts.admin :title="__('submission.tab_tips')" current="tips">
    <div class="page-head"><h1 class="t-h1">{{ __('submission.tab_tips') }}</h1><p class="t-small t-muted">{{ __('submission.tips_lead') }}</p></div>
    <div class="table-wrap" id="ai-live-list" data-ai-live>
        <table class="table">
            <thead><tr><th>{{ __('submission.col_receipt') }}</th><th>{{ __('submission.tip_url') }}</th><th>{{ __('submission.photos') }}</th><th>{{ __('submission.col_status') }}</th><th>{{ __('aistatus.title') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($tips as $tip)
                    <tr>
                        <td><code>{{ $tip->receipt_no }}</code><br><span class="t-small t-muted">{{ $tip->created_at?->format('Y-m-d H:i') }}</span></td>
                        <td>{{ $tip->text('source_url') ?? '—' }}@if (($tip->payload['inspection']['status'] ?? null) === 'robots_blocked')<br><span class="pill pill-warning">{{ __('submission.inspection_robots_blocked') }}</span>@endif</td>
                        <td>{{ $tip->media_count }}</td>
                        <td><span class="pill">{{ $tip->status->label() }}</span></td>
                        <td><x-ai-badge :status="app(\App\Services\Ai\AiStatusService::class)->forSubmission($tip)" /></td>
                        <td class="actions"><a class="btn btn-sm" href="{{ route('admin.tips.show', $tip) }}">{{ __('submission.open') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $tips->links() }}
</x-layouts.admin>