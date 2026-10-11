@php
    $all = \App\Models\User::query()->count();
    $admins = \App\Models\User::query()->where('role', \App\Enums\UserRole::Admin)->count();
    $trusted = \App\Models\User::query()->where('approved_count', '>=', app(\App\Services\Setting\SettingsService::class)->int(\App\Enums\SettingKey::ReviewAutoApproveMinApproved))->count();
    $suspended = \App\Models\User::query()->where('status', \App\Enums\UserStatus::Suspended)->count();
    $trustMin = max(1, app(\App\Services\Setting\SettingsService::class)->int(\App\Enums\SettingKey::ReviewAutoApproveMinApproved));
@endphp
<x-layouts.admin :title="__('users.title')" current="users">
    <div class="page-head"><h1 class="t-h1">{{ __('users.title') }}</h1><p class="t-small t-muted">{{ __('users.lead') }}</p></div>
    <form method="get" action="{{ route('admin.users') }}" class="filter-bar">
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('users.search') }}" aria-label="{{ __('users.search') }}">
        <a class="chip" href="{{ route('admin.users', array_filter(['q' => $q ?: null])) }}" @if ($role === null && $status === null) aria-current="page" @endif>{{ __('users.chip_all') }} {{ $all }}</a>
        <a class="chip" href="{{ route('admin.users', array_filter(['role' => 'admin', 'q' => $q ?: null])) }}" @if ($role === \App\Enums\UserRole::Admin) aria-current="page" @endif>{{ __('users.chip_admin') }} {{ $admins }}</a>
        <span class="chip" aria-disabled="true">{{ __('users.chip_trusted') }} {{ $trusted }}</span>
        <a class="chip" href="{{ route('admin.users', array_filter(['status' => 'suspended', 'q' => $q ?: null])) }}" @if ($status === \App\Enums\UserStatus::Suspended) aria-current="page" @endif>{{ __('users.chip_suspended') }} {{ $suspended }}</a>
    </form>
    <h2 class="board-h2">{{ __('users.list_title') }}</h2>
    <div class="card board-table">
        <table>
            <thead><tr><th>{{ __('users.col_name') }}</th><th>{{ __('users.col_role') }}</th><th>{{ __('users.col_approved') }}</th><th>{{ __('users.col_status') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($users as $u)
                    @php($rejected = \App\Models\Submission::query()->where('user_id', $u->id)->whereIn('status', [\App\Enums\SubmissionStatus::Rejected, \App\Enums\SubmissionStatus::AutoRejected])->count())
                    <tr>
                        <td><strong>{{ $u->name }}</strong><br><span class="t-small t-muted">{{ __('users.registered', ['date' => $u->created_at?->isoFormat('YYYY年M月')]) }}・{{ __('users.last_login', ['when' => $u->last_login_at?->diffForHumans() ?? '—']) }}</span></td>
                        <td>@if ($u->role === \App\Enums\UserRole::Member)<span>{{ $u->role->label() }}</span>@else<span class="status-tag is-ok">{{ $u->role->label() }}</span>@endif</td>
                        <td>@if ($u->approved_count === 0 && $rejected === 0)<span class="t-muted">—</span>@else{{ __('users.record', ['approved' => $u->approved_count, 'rejected' => $rejected]) }}@if ($u->approved_count >= $trustMin)<br><span class="t-small is-ok-text">{{ __('users.trusted_note') }}</span>@endif @endif</td>
                        <td>@if ($u->status === \App\Enums\UserStatus::Suspended)<span class="status-tag is-rejected">{{ __('users.suspended') }}</span>@else{{ __('users.active') }}@endif</td>
                        <td class="actions"><a class="btn btn-sm" href="{{ route('admin.users.show', $u) }}">{{ __('users.show') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
    <section class="card board-card"><h2>{{ __('users.suspend_title') }}</h2><p class="t-muted">{{ __('users.suspend_body') }}</p></section>
    <p class="t-small t-muted">{{ __('users.email_note') }}</p>
</x-layouts.admin>