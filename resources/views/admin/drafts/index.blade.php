<x-layouts.admin :title="__('ai.draft_title')" current="drafts">
    <div class="page-head"><h1 class="t-h1">{{ __('ai.draft_title') }}</h1><p class="t-small">{{ __('ai.draft_today', ['count' => $aiToday]) }}</p></div>
    <form method="post" action="{{ route('admin.drafts.read') }}" class="card draft-top">
        @csrf
        <div class="field">
            <label for="url">{{ __('ai.draft_url') }}</label>
            <input id="url" name="url" type="url" value="{{ old('url', $url) }}" maxlength="500" required placeholder="https://">
            @error('url')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <button class="btn btn-primary" type="submit">{{ $result && $result['status'] === 'limited' ? __('ai.retry') : __('ai.draft_read') }}</button>
        <p class="t-small t-muted draft-help">{{ __('ai.draft_lead') }}</p>
    </form>

    <div class="draft-grid">
    <div>
    @if ($result)
        @if ($result['status'] !== 'ok')
            <p class="alert alert-warning" role="status">{{ $result['reason'] }}</p>
        @elseif (! $result['draft']->isEvent)
            <p class="alert alert-warning" role="status">{{ __('ai.draft_not_event') }}</p>
        @else
            @php($d = $result['draft'])
            <form method="post" action="{{ route('admin.drafts.save') }}" class="card draft-result">
                @csrf
                <h2 class="t-h2">{{ __('ai.draft_result') }}</h2>
                <p class="t-small t-muted">{{ __('ai.draft_confidence', ['value' => number_format($d->confidence, 2)]) }}@if ($d->isCancelled) / {{ __('layout.status_cancelled') }}@endif</p>
                <input type="hidden" name="source_url" value="{{ $url }}">
                <div class="field"><label for="d-title">{{ __('content.field_title') }}</label><input id="d-title" name="title" value="{{ $d->title }}" maxlength="200" required></div>
                <div class="field"><label for="d-region">{{ __('crawl.region') }}</label><x-region-select name="region_id" id="d-region" /></div>
                <div class="repeat-row">
                    <div class="field"><label for="d-start">{{ __('ai.start_date') }}</label><input id="d-start" name="start_date" type="date" value="{{ $d->startDate }}" required></div>
                    <div class="field"><label for="d-end">{{ __('ai.end_date') }}</label><input id="d-end" name="end_date" type="date" value="{{ $d->endDate }}"></div>
                    <div class="field"><label for="d-st">{{ __('ai.start_time') }}</label><input id="d-st" name="start_time" type="time" value="{{ $d->startTime }}"></div>
                    <div class="field"><label for="d-et">{{ __('ai.end_time') }}</label><input id="d-et" name="end_time" type="time" value="{{ $d->endTime }}"></div>
                </div>
                <div class="field"><label for="d-venue">{{ __('public.venue') }}</label><input id="d-venue" name="venue" value="{{ $d->venue }}" maxlength="200"></div>
                <div class="field"><label for="d-address">{{ __('public.address') }}</label><input id="d-address" name="address" value="{{ $d->address }}" maxlength="300"></div>
                <div class="field"><label for="d-fee">{{ __('public.fee') }}</label><input id="d-fee" name="fee" value="{{ $d->fee }}" maxlength="200"></div>
                <p class="t-small t-muted">{{ __('ai.draft_save_help') }}</p>
                <button class="btn btn-primary" type="submit">{{ __('ai.draft_save') }}</button>
            </form>
        @endif
    @endif
    </div>
    <aside class="draft-side">
        <section class="card board-card"><h2>{{ __('ai.draft_recent') }}</h2>
            <ul class="draft-recent">@forelse ($recent as $e)<li><span>{{ $e->title }}</span><span class="t-small t-muted">{{ $e->created_at?->diffForHumans() }}</span></li>@empty<li class="t-muted">{{ __('submission.review_empty') }}</li>@endforelse</ul>
        </section>
    </aside>
    </div>
</x-layouts.admin>