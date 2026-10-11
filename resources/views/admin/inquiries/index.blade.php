@php
    $chips = [
        ['label' => __('inquiry.chip_open'), 'params' => [], 'count' => $counts['open'], 'active' => $kind === null && $status === null],
        ['label' => __('inquiry.chip_all'), 'params' => ['status' => 'all'], 'count' => $counts['all'], 'active' => $kind === null && $request_all],
    ];
    foreach ($kinds as $k) {
        $chips[] = ['label' => __('inquiry.chip_kind.'.$k->value), 'params' => ['kind' => $k->value], 'count' => $counts['kinds'][$k->value] ?? 0, 'active' => $kind === $k];
    }
@endphp
<x-layouts.admin :title="__('inquiry.admin_title')" current="inquiries">
    <div class="page-head">
        <h1 class="t-h1">{{ __('inquiry.admin_title') }}</h1>
        <p class="t-small t-muted">{{ __('inquiry.admin_lead') }}</p>
    </div>
    <nav class="filter-bar" aria-label="{{ __('inquiry.admin_title') }}">
        @foreach ($chips as $chip)
            <a class="chip" href="{{ route('admin.inquiries', $chip['params']) }}" @if ($chip['active']) aria-current="page" @endif>{{ $chip['label'] }} {{ $chip['count'] }}</a>
        @endforeach
    </nav>
    <div class="card board-table">
        <table>
            <thead><tr><th>{{ __('inquiry.admin_col_receipt') }}</th><th>{{ __('inquiry.admin_col_kind') }}</th><th>{{ __('inquiry.admin_col_body') }}</th><th>{{ __('inquiry.admin_col_mail') }}</th><th>{{ __('inquiry.admin_col_status') }}</th></tr></thead>
            <tbody>
                @forelse ($inquiries as $i)
                    <tr @class(['is-urgent' => $i->urgent])>
                        <td><a class="plain-link" href="{{ route('admin.inquiries.show', $i) }}"><strong>{{ $i->receipt_no }}</strong></a><br><span class="t-small t-muted">{{ $i->created_at?->setTimezone('Asia/Tokyo')->isoFormat('M/D H:mm') }}</span></td>
                        <td><span class="status-tag {{ $i->kind === \App\Enums\InquiryKind::Takedown ? 'is-warn' : 'is-ok' }}">{{ __('inquiry.chip_kind.'.$i->kind->value) }}</span></td>
                        <td>@if ($i->urgent)<span class="status-tag is-rejected">{{ __('inquiry.admin_urgent') }}</span> @endif{{ \Illuminate\Support\Str::limit(str_replace("\n", ' ', $i->body), 60) }}</td>
                        <td>{{ $i->email ? __('inquiry.mail_yes') : __('inquiry.mail_no') }}</td>
                        <td><span @class(['status-tag', 'is-rejected' => $i->status === \App\Enums\InquiryStatus::New, 'is-warn' => $i->status === \App\Enums\InquiryStatus::InProgress, 'is-ok' => $i->status === \App\Enums\InquiryStatus::Done])>{{ $i->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="t-muted">{{ __('inquiry.admin_none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $inquiries->links() }}
    <p class="t-small t-muted">{{ __('inquiry.admin_note') }}</p>
</x-layouts.admin>