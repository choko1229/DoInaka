<x-layouts.admin :title="__('users.title')" current="users">
    <div class="page-head"><h1 class="t-h1">{{ __('users.title') }}</h1><p class="t-small t-muted">{{ __('users.lead') }}</p></div>
    <form method="get" action="{{ route('admin.users') }}" class="inline-form">
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('users.search') }}" aria-label="{{ __('users.search') }}">
        <select name="role" aria-label="{{ __('users.col_role') }}"><option value="">{{ __('users.all') }}</option>@foreach (\App\Enums\UserRole::cases() as $r)<option value="{{ $r->value }}" @selected($role === $r)>{{ $r->label() }}</option>@endforeach</select>
        <select name="status" aria-label="{{ __('users.col_status') }}"><option value="">{{ __('users.all') }}</option>@foreach (\App\Enums\UserStatus::cases() as $s)<option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>@endforeach</select>
        <button class="btn btn-sm" type="submit">{{ __('public.apply') }}</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('users.col_name') }}</th><th>{{ __('users.col_role') }}</th><th>{{ __('users.col_status') }}</th><th>{{ __('users.col_approved') }}</th><th>{{ __('users.col_last_login') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->role->label() }}</td>
                        <td><span class="pill {{ $u->status === \App\Enums\UserStatus::Suspended ? 'pill-failed' : 'pill-success' }}">{{ $u->status->label() }}</span></td>
                        <td>{{ $u->approved_count }}</td>
                        <td>{{ $u->last_login_at?->format('Y-m-d') ?? '—' }}</td>
                        <td class="actions"><a class="btn btn-sm" href="{{ route('admin.users.show', $u) }}">{{ __('users.show') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</x-layouts.admin>