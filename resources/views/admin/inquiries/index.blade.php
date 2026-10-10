<x-layouts.admin :title="__('inquiry.admin_title')" current="inquiries">
    <div class="page-head">
        <h1 class="t-h1">{{ __('inquiry.admin_title') }}</h1>
        <p class="t-small t-muted">{{ __('inquiry.admin_new_count', ['count' => $counts['new']]) }} / {{ __('inquiry.admin_urgent_count', ['count' => $counts['urgent']]) }}</p>
    </div>
    <form method="get" action="{{ route('admin.inquiries') }}" class="inline-form">
        <select name="kind" aria-label="{{ __('inquiry.admin_col_kind') }}"><option value="">{{ __('inquiry.admin_filter_all') }}</option>@foreach ($kinds as $k)<option value="{{ $k->value }}" @selected($kind === $k)>{{ $k->label() }}</option>@endforeach</select>
        <select name="status" aria-label="{{ __('inquiry.admin_col_status') }}"><option value="">{{ __('inquiry.admin_status_open') }}</option>@foreach ($statuses as $s)<option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>@endforeach</select>
        <button class="btn btn-sm" type="submit">{{ __('public.apply') }}</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('inquiry.admin_col_receipt') }}</th><th>{{ __('inquiry.admin_col_kind') }}</th><th>{{ __('inquiry.admin_col_status') }}</th><th>{{ __('inquiry.admin_col_received') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($inquiries as $i)
                    <tr>
                        <td><code>{{ $i->receipt_no }}</code> @if ($i->urgent)<span class="pill pill-failed">{{ __('inquiry.admin_urgent') }}</span>@endif</td>
                        <td>{{ $i->kind->label() }}</td>
                        <td>{{ $i->status->label() }}</td>
                        <td>{{ $i->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="actions"><a class="btn btn-sm" href="{{ route('admin.inquiries.show', $i) }}">{{ __('users.show') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="t-muted">{{ __('inquiry.admin_none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $inquiries->links() }}
</x-layouts.admin>