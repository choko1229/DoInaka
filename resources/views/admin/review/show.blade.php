<x-layouts.admin :title="$submission->receipt_no" current="review">
    <div class="page-head">
        <h1 class="t-h1">{{ $submission->type->label() }} <code>{{ $submission->receipt_no }}</code></h1>
        <a href="{{ route('admin.review') }}">{{ __('submission.back_to_list') }}</a>
    </div>

    <dl class="facts">
        <dt>{{ __('submission.col_status') }}</dt><dd>{{ $submission->status->label() }}@if ($submission->reject_reason) — {{ $submission->reject_reason }}@endif</dd>
        <dt>{{ __('submission.col_from') }}</dt><dd>{{ $submission->user?->name ?? __('submission.anonymous') }}@if ($submission->user) ({{ __('submission.approved_count', ['count' => $submission->user->approved_count]) }})@endif</dd>
        <dt>{{ __('submission.received_at') }}</dt><dd>{{ $submission->created_at?->format('Y-m-d H:i') }}</dd>
        <dt>{{ __('submission.consent') }}</dt><dd>{{ $submission->consented_at?->format('Y-m-d H:i') }}({{ $submission->terms_version }})</dd>
        @if ($submission->target_type)<dt>{{ __('submission.target') }}</dt><dd>{{ $submission->target_type }} #{{ $submission->target_id }}</dd>@endif
        @if ($submission->reviewer)<dt>{{ __('submission.reviewer') }}</dt><dd>{{ $submission->reviewer->name }} {{ $submission->reviewed_at?->format('Y-m-d H:i') }}</dd>@endif
    </dl>

    <section class="card">
        <h2 class="t-h2">{{ __('submission.content') }}</h2>
        <dl class="facts">
            @foreach (($submission->payload ?? []) as $key => $value)
                @if ($key !== 'inspection' && ! is_array($value))
                    <dt>{{ __('submission.attributes.'.$key) !== 'submission.attributes.'.$key ? __('submission.attributes.'.$key) : $key }}</dt>
                    <dd>{!! nl2br(e((string) $value)) !!}</dd>
                @endif
            @endforeach
        </dl>
        @if ($correction)
            <h3 class="t-h3">{{ __('submission.correction_diff') }}</h3>
            <dl class="facts">
                <dt>{{ __('submission.fields.'.$correction->field) }}({{ __('submission.now') }})</dt><dd>{{ $currentValue }}</dd>
                <dt>{{ __('submission.proposed') }}</dt><dd>{{ $correction->proposed_value }}</dd>
                @if ($correction->source_url)<dt>{{ __('submission.report_source') }}</dt><dd><a href="{{ $correction->source_url }}" rel="nofollow noopener" target="_blank">{{ $correction->source_url }}</a></dd>@endif
            </dl>
        @endif
        @if (isset($submission->payload['inspection']))
            <p class="t-small">{{ __('submission.inspection', ['status' => __('submission.inspection_'.$submission->payload['inspection']['status'])]) }}@if ($submission->payload['inspection']['candidate']) {{ __('submission.inspection_candidate') }}@endif</p>
        @endif
    </section>

    @if ($submission->media->isNotEmpty())
        <section class="card">
            <h2 class="t-h2">{{ __('submission.photos') }}</h2>
            <div class="card-grid">
                @foreach ($submission->media as $media)
                    <figure>
                        <a href="{{ route('admin.media.original', $media) }}" target="_blank" rel="noopener">
                            @if ($media->isProcessed())
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url((string) $media->path_small) }}" alt="" loading="lazy" style="max-width:100%">
                            @else
                                <span class="t-small">{{ __('submission.original_only') }}</span>
                            @endif
                        </a>
                        <figcaption class="t-small t-muted">{{ $media->width }}×{{ $media->height }}@if ($media->credit) · {{ $media->credit }}@endif @if ($media->rights_agreed_at) · {{ __('submission.rights_ok') }}@endif</figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    <section class="card">
        @if ($canApprove)
            <form method="post" action="{{ route('admin.review.approve', $submission) }}" class="inline-form">@csrf<button class="btn btn-primary" type="submit">{{ __('submission.approve') }}</button></form>
            <form method="post" action="{{ route('admin.review.reject', $submission) }}" class="inline-form">
                @csrf
                <input type="text" name="reason" maxlength="300" placeholder="{{ __('submission.reject_reason') }}" aria-label="{{ __('submission.reject_reason') }}">
                <button class="btn" type="submit">{{ __('submission.reject') }}</button>
            </form>
        @endif
        @if ($submission->status->isRejected())
            <form method="post" action="{{ route('admin.review.restore', $submission) }}" class="inline-form">@csrf<button class="btn" type="submit">{{ __('submission.restore') }}</button></form>
        @endif
    </section>
</x-layouts.admin>