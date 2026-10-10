@php($ai = $inquiry->ai_check)
<x-layouts.admin :title="__('inquiry.admin_title')" current="inquiries">
    <div class="page-head">
        <h1 class="t-h1"><code>{{ $inquiry->receipt_no }}</code> @if ($inquiry->urgent)<span class="pill pill-failed">{{ __('inquiry.admin_urgent') }}</span>@endif</h1>
        <p class="t-small t-muted">{{ $inquiry->kind->label() }} / {{ $inquiry->status->label() }} / {{ $inquiry->created_at?->format('Y-m-d H:i') }}</p>
        <p><a href="{{ route('admin.inquiries') }}">← {{ __('inquiry.admin_title') }}</a></p>
    </div>

    <section class="card">
        <dl class="kv">
            @if ($inquiry->target_url)<dt>{{ __('inquiry.admin_target') }}</dt><dd>{{ $inquiry->target_url }}</dd>@endif
            @if ($inquiry->right_type)<dt>{{ __('inquiry.admin_right') }}</dt><dd>{{ $inquiry->right_type->label() }}</dd>@endif
            @if ($inquiry->organizer_name)<dt>{{ __('inquiry.admin_organizer') }}</dt><dd>{{ $inquiry->organizer_name }}</dd>@endif
            <dt>{{ __('inquiry.admin_email') }}</dt><dd>{{ $inquiry->email ?: __('inquiry.admin_email_none') }}</dd>
            @if ($inquiry->result)<dt>{{ __('inquiry.admin_result') }}</dt><dd>{{ $inquiry->result === 'removed' ? __('inquiry.admin_result_removed') : __('inquiry.admin_result_kept') }}</dd>@endif
        </dl>
        <p class="prose" style="white-space:pre-wrap">{{ $inquiry->body }}</p>
    </section>

    @if ($inquiry->kind === \App\Enums\InquiryKind::Takedown)
        <section class="card">
            <h2 class="t-h2">{{ __('inquiry.admin_ai') }}</h2>
            @if (($ai['status'] ?? null) === 'ok')
                <p>{{ __('inquiry.admin_ai_verdict.'.$ai['verdict']) }}({{ number_format((float) ($ai['confidence'] ?? 0), 2) }})</p>
                <ul class="t-small">@foreach (($ai['reasons'] ?? []) as $reason)<li>{{ $reason }}</li>@endforeach</ul>
            @elseif (($ai['status'] ?? null) !== null)
                <p class="t-muted">{{ __('inquiry.admin_ai_unavailable') }}</p>
            @else
                <p class="t-muted">{{ __('inquiry.admin_ai_pending') }}</p>
            @endif

            <h2 class="t-h2">{{ __('inquiry.admin_hold') }}</h2>
            @forelse ($inquiry->holds as $hold)
                <p class="t-small">
                    @if ($hold->media_id)
                        {{ __('inquiry.admin_hold_photo', ['id' => $hold->media_id, 'reveal' => $hold->reveal_allowed ? __('inquiry.admin_hold_reveal_yes') : __('inquiry.admin_hold_reveal_no')]) }}
                    @else
                        {{ __('inquiry.admin_hold_page') }}
                    @endif
                    @if ($hold->released_at)({{ $hold->released_at->format('Y-m-d') }})@endif
                </p>
            @empty
                <p class="t-muted t-small">{{ __('inquiry.admin_hold_none') }}</p>
            @endforelse

            <h2 class="t-h2">{{ __('inquiry.admin_consent') }}</h2>
            @if ($inquiry->consent)
                <p>{{ $inquiry->consent->status->label() }}({{ __('inquiry.admin_consent_deadline', ['date' => $inquiry->consent->deadline_at->format('Y-m-d')]) }})</p>
                @if ($inquiry->consent->allowsRemoval())<p class="t-small">{{ __('inquiry.admin_consent_removable') }}</p>@endif
                @if ($inquiry->consent->objection_reason)<p class="t-small" style="white-space:pre-wrap">{{ __('inquiry.admin_consent_objection', ['reason' => $inquiry->consent->objection_reason]) }}</p>@endif
            @else
                <p class="t-muted t-small">{{ __('inquiry.admin_consent_none') }}</p>
            @endif

            @if ($inquiry->holds->whereNull('released_at')->isNotEmpty())
                <div class="actions">
                    <form method="post" action="{{ route('admin.inquiries.remove', $inquiry) }}" data-confirm="{{ __('inquiry.admin_remove_confirm') }}">@csrf<button class="btn btn-danger" type="submit">{{ __('inquiry.admin_remove') }}</button></form>
                    <form method="post" action="{{ route('admin.inquiries.keep', $inquiry) }}" data-confirm="{{ __('inquiry.admin_keep_confirm') }}">@csrf<button class="btn" type="submit">{{ __('inquiry.admin_keep') }}</button></form>
                </div>
            @endif
        </section>
    @endif

    <section class="card">
        <h2 class="t-h2">{{ __('inquiry.admin_set_status') }}</h2>
        <form method="post" action="{{ route('admin.inquiries.status', $inquiry) }}" class="inline-form">
            @csrf
            <select name="status" aria-label="{{ __('inquiry.admin_col_status') }}">@foreach ($statuses as $s)<option value="{{ $s->value }}" @selected($inquiry->status === $s)>{{ $s->label() }}</option>@endforeach</select>
            <button class="btn btn-sm" type="submit">{{ __('inquiry.admin_set_status') }}</button>
        </form>
    </section>

    <section class="card">
        <h2 class="t-h2">{{ __('inquiry.admin_reply') }}</h2>
        @if ($inquiry->email)
            <form method="post" action="{{ route('admin.inquiries.reply', $inquiry) }}">
                @csrf
                <div class="field"><label for="body">{{ __('inquiry.admin_reply_body') }}</label><textarea id="body" name="body" rows="6" maxlength="5000" required></textarea></div>
                <button class="btn btn-primary" type="submit">{{ __('inquiry.admin_reply') }}</button>
            </form>
        @else
            <p class="t-muted">{{ __('inquiry.no_email') }}</p>
        @endif

        <h3 class="t-h3">{{ __('inquiry.admin_reply_history') }}</h3>
        <ul class="t-small">
            @foreach ($inquiry->replies as $reply)
                <li>
                    {{ $reply->created_at?->format('Y-m-d H:i') }} / {{ $reply->status->label() }}
                    @if ($reply->status === \App\Enums\ReplyStatus::Failed)
                        — {{ __('inquiry.admin_failed', ['reason' => $reply->failure ?? '']) }}
                        @if ($reply->failure !== 'no_email')
                            <form method="post" action="{{ route('admin.inquiries.retry', $reply) }}" style="display:inline">@csrf<button class="link-button" type="submit">{{ __('inquiry.admin_retry') }}</button></form>
                        @endif
                    @endif
                    <div style="white-space:pre-wrap">{{ $reply->body }}</div>
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.admin>