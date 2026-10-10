<x-layouts.admin :title="__('logs.title')" current="logs">
    <div class="page-head"><h1 class="t-h1">{{ __('logs.title') }}</h1><p class="t-small t-muted">{{ __('logs.lead') }}</p></div>
    @include('admin.logs._tabs')

    <form method="get" action="{{ route('admin.logs', ['tab' => $tab]) }}" class="inline-form">
        <label>{{ __('logs.from') }} <input type="date" name="from" value="{{ $filters['from'] }}"></label>
        <label>{{ __('logs.to') }} <input type="date" name="to" value="{{ $filters['to'] }}"></label>
        @if ($tab === 'operations')
            <select name="action" aria-label="{{ __('logs.action') }}"><option value="">{{ __('logs.all') }}</option>@foreach (\App\Enums\AuditAction::cases() as $a)<option value="{{ $a->value }}" @selected($filters['action'] === $a->value)>{{ $a->value }}</option>@endforeach</select>
            <input type="number" name="user" min="1" value="{{ $filters['user'] }}" placeholder="{{ __('logs.user_id') }}" aria-label="{{ __('logs.user_id') }}">
        @elseif ($tab === 'reviews')
            <select name="type" aria-label="{{ __('submission.col_type') }}"><option value="">{{ __('logs.all') }}</option>@foreach (\App\Enums\SubmissionType::cases() as $t)<option value="{{ $t->value }}" @selected($filters['type'] === $t->value)>{{ $t->label() }}</option>@endforeach</select>
            <select name="status" aria-label="{{ __('submission.col_status') }}"><option value="">{{ __('logs.all') }}</option>@foreach ([\App\Enums\SubmissionStatus::Approved, \App\Enums\SubmissionStatus::Rejected, \App\Enums\SubmissionStatus::AutoRejected] as $s)<option value="{{ $s->value }}" @selected($filters['status'] === $s->value)>{{ $s->label() }}</option>@endforeach</select>
        @else
            <select name="purpose" aria-label="{{ __('logs.purpose') }}"><option value="">{{ __('logs.all') }}</option>@foreach (\App\Enums\AiPurpose::cases() as $p)<option value="{{ $p->value }}" @selected($filters['purpose'] === $p->value)>{{ $p->label() }}</option>@endforeach</select>
            <select name="status" aria-label="{{ __('submission.col_status') }}"><option value="">{{ __('logs.all') }}</option>@foreach (['ok', 'error', 'invalid', 'rate_limited'] as $s)<option value="{{ $s }}" @selected($filters['status'] === $s)>{{ $s }}</option>@endforeach</select>
        @endif
        <button class="btn btn-sm" type="submit">{{ __('public.apply') }}</button>
        <a class="btn btn-sm" href="{{ route('admin.logs.csv', array_merge(['tab' => $tab], request()->query())) }}">{{ __('logs.csv') }}</a>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr>@foreach ($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>@foreach ($row as $cell)<td>{{ is_array($cell) ? \Illuminate\Support\Str::limit((string) json_encode($cell, JSON_UNESCAPED_UNICODE), 120) : \Illuminate\Support\Str::limit((string) $cell, 120) }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($headers) }}" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $paginator->links() }}
</x-layouts.admin>