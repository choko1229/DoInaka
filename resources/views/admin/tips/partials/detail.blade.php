    <section class="card">
        <dl class="facts">
            <dt>{{ __('submission.tip_url') }}</dt>
            <dd>@if ($submission->text('source_url'))<a href="{{ $submission->text('source_url') }}" rel="nofollow noopener" target="_blank">{{ $submission->text('source_url') }}</a>@else — @endif</dd>
            @if ($inspection)<dt>{{ __('submission.inspection_label') }}</dt><dd>{{ __('submission.inspection_'.$inspection['status']) }}@if ($inspection['candidate']) / {{ __('submission.inspection_candidate') }}@endif</dd>@endif
            <dt>{{ __('submission.tip_note') }}</dt><dd>{!! nl2br(e($submission->text('note') ?? '—')) !!}</dd>
            <dt>{{ __('submission.col_from') }}</dt><dd>{{ $submission->user?->name ?? __('submission.anonymous') }}</dd>
        </dl>
        <p class="t-small t-muted">{{ __('submission.tip_note_use') }}</p>
    </section>

    @php($aiStatus = app(\App\Services\Ai\AiStatusService::class)->forSubmission($submission))
    @if ($aiStatus->state !== \App\Enums\AiState::None)
        <section class="card" id="ai-live-detail" data-ai-live>
            <h2 class="t-h2">{{ __('submission.ai_result') }}</h2>
            <x-ai-detail :status="$aiStatus" />
        </section>
    @endif

    @if ($submission->media->isNotEmpty())
        <section class="card">
            <h2 class="t-h2">{{ __('submission.tip_photos') }}</h2>
            <p class="t-small t-muted">{{ __('submission.mask_help') }}</p>
            @error('masked')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            @foreach ($submission->media as $media)
                <div class="repeat-row">
                    <a href="{{ route('admin.media.original', $media) }}" target="_blank" rel="noopener">{{ __('submission.view_original') }} #{{ $media->id }}</a>
                    @if ($media->isProcessed())
                        <span><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url((string) $media->path_small) }}" alt="" style="max-width:160px"> <span class="pill pill-success">{{ __('submission.masked_done') }}</span></span>
                    @endif
                    <form method="post" action="{{ route('admin.tips.mask', [$submission, $media]) }}" enctype="multipart/form-data" class="inline-form">
                        @csrf
                        <input type="file" name="masked" accept="image/jpeg,image/png,image/webp" required aria-label="{{ __('submission.masked_file') }}">
                        <button class="btn btn-sm" type="submit">{{ __('submission.masked_register') }}</button>
                    </form>
                </div>
            @endforeach
        </section>
    @endif

    <section class="card">
        <h2 class="t-h2">{{ __('submission.tip_make_draft') }}</h2>
        <p class="t-small t-muted">{{ __('submission.tip_make_draft_help') }}</p>
        <form method="get" action="{{ route('admin.events.create') }}" class="inline-form">
            <input type="hidden" name="tip" value="{{ $submission->id }}">
            <select name="series" required aria-label="{{ __('content.col_series') }}">
                <option value="">—</option>
                @foreach ($series as $item)<option value="{{ $item->id }}">{{ $item->title }}</option>@endforeach
            </select>
            <button class="btn" type="submit">{{ __('submission.tip_make_draft_button') }}</button>
            <a class="btn" href="{{ route('admin.series.create') }}">{{ __('content.series_add') }}</a>
        </form>
    </section>

    <section class="card">
        <form method="post" action="{{ route('admin.review.approve', $submission) }}" class="inline-form">@csrf<button class="btn btn-primary" type="submit">{{ __('submission.tip_adopt') }}</button></form>
        <form method="post" action="{{ route('admin.review.reject', $submission) }}" class="inline-form">
            @csrf
            <input type="text" name="reason" maxlength="300" placeholder="{{ __('submission.reject_reason') }}" aria-label="{{ __('submission.reject_reason') }}">
            <button class="btn" type="submit">{{ __('submission.tip_dismiss') }}</button>
        </form>
    </section>
