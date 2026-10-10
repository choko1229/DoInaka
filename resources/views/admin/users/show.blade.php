<x-layouts.admin :title="__('users.detail')" current="users">
    <div class="page-head"><h1 class="t-h1">{{ $member->name }}</h1><a href="{{ route('admin.users') }}">{{ __('submission.back_to_list') }}</a></div>
    <section class="card">
        <dl class="facts">
            <dt>{{ __('users.email') }}</dt><dd>{{ $member->email }} <span class="t-small t-muted">{{ __('users.email_notice') }}</span></dd>
            <dt>{{ __('users.col_role') }}</dt><dd>{{ $member->role->label() }}</dd>
            <dt>{{ __('users.col_status') }}</dt><dd>{{ $member->status->label() }}</dd>
            <dt>{{ __('users.col_approved') }}</dt><dd>{{ $member->approved_count }}</dd>
            <dt>{{ __('users.joined') }}</dt><dd>{{ $member->created_at?->format('Y-m-d') }}</dd>
            <dt>{{ __('users.col_last_login') }}</dt><dd>{{ $member->last_login_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            @if ($member->bio)<dt>{{ __('users.bio') }}</dt><dd>{!! nl2br(e($member->bio)) !!}</dd>@endif
        </dl>
    </section>
    <section class="card">
        <form method="post" action="{{ route('admin.users.role', $member) }}" class="inline-form">
            @csrf
            <label for="role">{{ __('users.role') }}</label>
            <select id="role" name="role">@foreach (\App\Enums\UserRole::cases() as $r)<option value="{{ $r->value }}" @selected($member->role === $r)>{{ $r->label() }}</option>@endforeach</select>
            <button class="btn btn-sm" type="submit">{{ __('users.role_save') }}</button>
        </form>
        <p class="t-small t-muted">{{ __('users.role_help') }}</p>
        @if ($isLastAdmin)<p class="alert alert-warning" role="status">{{ __('users.refuse_last_admin') }}</p>@endif
        @if ($member->status === \App\Enums\UserStatus::Active)
            <form method="post" action="{{ route('admin.users.suspend', $member) }}" data-confirm="{{ __('users.suspend_confirm') }}">@csrf<button class="btn" type="submit">{{ __('users.suspend') }}</button></form>
        @else
            <form method="post" action="{{ route('admin.users.restore', $member) }}">@csrf<button class="btn" type="submit">{{ __('users.restore') }}</button></form>
        @endif
    </section>
</x-layouts.admin>