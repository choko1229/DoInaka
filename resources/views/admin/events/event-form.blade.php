@php
    $scheduleRows = old('schedules') ?? $schedules->map(fn ($s) => [
        'date' => $s->date->toDateString(), 'start_time' => $s->start_time ? substr($s->start_time, 0, 5) : null,
        'end_time' => $s->end_time ? substr($s->end_time, 0, 5) : null, 'note' => $s->note, 'is_cancelled' => $s->is_cancelled,
    ])->all();
    $sourceRows = old('sources') ?? $sources->map(fn ($s) => [
        'kind' => $s->kind->value, 'url' => $s->url, 'title' => $s->title, 'checked_at' => $s->checked_at?->toDateString(), 'is_official' => $s->is_official,
    ])->all();
    if ($scheduleRows === []) { $scheduleRows = [['date' => null]]; }
    $state = old('state', $event->exists ? ($event->is_published ? 'published' : 'draft') : 'draft');
@endphp
<x-layouts.admin :title="__('content.event_edit')" current="events">
    <div class="page-head">
        <h1 class="t-h1">{{ $event->exists ? __('content.event_edit') : __('content.event_add') }}</h1>
        <p class="t-small t-muted">{{ __('content.revision_note') }}</p>
    </div>
    <p><a href="{{ route('admin.events', ['series' => $series->id]) }}">{{ __('content.back_events') }}</a> / {{ $series->title }}</p>

    @error('sources')<p class="alert alert-danger" role="alert">{{ $message }}</p>@enderror

    <form method="post" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}" class="grid-main-side">
        @csrf
        @if ($event->exists) @method('put') @else <input type="hidden" name="series_id" value="{{ $series->id }}"> @endif

        <div>
            <section class="card">
                <h2 class="t-h2">{{ __('content.section_basic') }}</h2>
                <div class="field">
                    <label for="title">{{ __('content.field_title') }}</label>
                    <input id="title" name="title" value="{{ old('title', $event->title) }}" required maxlength="200">
                    @error('title')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="category_id">{{ __('content.field_category') }}</label>
                    <select id="category_id" name="category_id">
                        <option value="">—</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $event->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="tags">{{ __('content.field_tags') }}</label>
                    <input id="tags" name="tags" value="{{ old('tags', $tagsText) }}" maxlength="300">
                    <p class="t-caption t-muted">{{ __('content.field_tags_help') }}</p>
                </div>
            </section>

            <section class="card">
                <h2 class="t-h2">{{ __('content.section_place') }}</h2>
                <div class="field">
                    <label for="region_id">{{ __('content.field_region') }}</label>
                    <x-region-select :selected="old('region_id', $event->region_id)" />
                    @error('region_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="venue_name">{{ __('content.field_venue') }}</label>
                    <input id="venue_name" name="venue_name" value="{{ old('venue_name', $event->venue_name) }}" maxlength="200">
                </div>
                <div class="field">
                    <label for="address">{{ __('content.field_address') }}</label>
                    <input id="address" name="address" value="{{ old('address', $event->address) }}" maxlength="300">
                    <p class="t-caption t-muted">{{ __('content.field_address_help') }}</p>
                </div>
                <div class="repeat-row">
                    <div class="field"><label for="lat">{{ __('content.field_lat') }}</label><input id="lat" name="lat" inputmode="decimal" value="{{ old('lat', $event->lat) }}"></div>
                    <div class="field"><label for="lng">{{ __('content.field_lng') }}</label><input id="lng" name="lng" inputmode="decimal" value="{{ old('lng', $event->lng) }}"></div>
                </div>
                <div class="field"><label for="fee">{{ __('content.field_fee') }}</label><input id="fee" name="fee" value="{{ old('fee', $event->fee) }}" maxlength="200"></div>
                <div class="field"><label for="url">{{ __('content.field_url') }}</label><input id="url" name="url" type="url" value="{{ old('url', $event->url) }}" maxlength="500"></div>
            </section>

            <section class="card">
                <h2 class="t-h2">{{ __('content.section_body') }}</h2>
                <div class="field">
                    <label for="body">{{ __('content.field_body') }}</label>
                    <textarea id="body" name="body" maxlength="20000">{{ old('body', $event->body) }}</textarea>
                    <p class="t-caption t-muted">{{ __('content.field_body_help') }}</p>
                </div>
            </section>

            <section class="card">
                <h2 class="t-h2">{{ __('content.section_sources') }} <span class="t-small" style="color:var(--danger)">{{ __('content.required_one') }}</span></h2>
                <p class="t-small t-muted">{{ __('content.sources_help') }}</p>
                <div data-repeat-target="sources" data-next-index="{{ count($sourceRows) }}">
                    @foreach (array_values($sourceRows) as $i => $row)
                        @include('admin.events.partials.source-row', ['i' => $i, 'row' => $row])
                    @endforeach
                </div>
                <template data-repeat-template="sources">@include('admin.events.partials.source-row', ['i' => '__INDEX__', 'row' => ['kind' => 'url']])</template>
                <x-button type="button" size="sm" data-repeat-add="sources">{{ __('content.source_add') }}</x-button>
            </section>

            <section class="card">
                <h2 class="t-h2">{{ __('content.section_schedules') }}</h2>
                <div data-repeat-target="schedules" data-next-index="{{ count($scheduleRows) }}">
                    @foreach (array_values($scheduleRows) as $i => $row)
                        @include('admin.events.partials.schedule-row', ['i' => $i, 'row' => $row])
                    @endforeach
                </div>
                <template data-repeat-template="schedules">@include('admin.events.partials.schedule-row', ['i' => '__INDEX__', 'row' => []])</template>
                <x-button type="button" size="sm" data-repeat-add="schedules">{{ __('content.schedule_add') }}</x-button>
                <p class="t-caption t-muted">{{ __('content.schedule_help') }}</p>
            </section>

            <section class="card">
                <h2 class="t-h2">{{ __('content.section_url') }}</h2>
                <div class="field">
                    <label for="slug">{{ __('content.field_slug') }}</label>
                    <input id="slug" name="slug" value="{{ old('slug', $event->slug) }}" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                    <p class="t-caption t-muted">{{ __('content.field_slug_help') }}</p>
                </div>
            </section>
        </div>

        <aside class="card">
            <h2 class="t-h2">{{ __('content.section_publish') }}</h2>
            <fieldset class="field">
                <legend>{{ __('content.field_state') }}</legend>
                <label><input type="radio" name="state" value="published" @checked($state === 'published')> {{ __('content.state_published') }}</label>
                <label><input type="radio" name="state" value="draft" @checked($state !== 'published')> {{ __('content.state_draft') }}</label>
            </fieldset>
            @if ($event->exists)
                <label class="check-row"><input type="checkbox" name="is_postponed" value="1" @checked(old('is_postponed', $event->is_postponed))><span>{{ __('content.field_postponed') }}</span></label>
            @endif
            <div class="field">
                <label for="reason">{{ __('content.field_reason') }}</label>
                <input id="reason" name="reason" maxlength="200" value="{{ old('reason') }}">
                <p class="t-caption t-muted">{{ __('content.field_reason_help') }}</p>
            </div>
            <x-button type="submit" variant="primary">{{ __('content.save') }}</x-button>
            @if ($event->exists)
                <p class="t-caption t-muted">{{ __('content.last_updated', ['at' => $event->updated_at?->setTimezone('Asia/Tokyo')->format('n/j G:i')]) }}</p>
                <p><a href="{{ route('admin.revisions', ['type' => 'event', 'id' => $event->id]) }}">{{ __('content.history_count', ['count' => $revisionCount]) }}</a></p>
            @endif
        </aside>
    </form>

    @if ($event->exists)
        <section class="card">
            <h2 class="t-h3">{{ __('content.danger_zone') }}</h2>
            <div class="button-row">
                <form method="post" action="{{ route('admin.events.cancel', $event) }}">@csrf<x-button type="submit">{{ __('content.cancel_event') }}</x-button></form>
                <form method="post" action="{{ route('admin.events.copy', $event) }}">@csrf<x-button type="submit">{{ __('content.copy_next_year') }}</x-button></form>
                <form method="post" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm(@js(__('content.delete_confirm')))">@csrf @method('delete')<x-button type="submit">{{ __('content.delete_mistake') }}</x-button></form>
            </div>
            <p class="t-caption t-muted">{{ __('content.delete_help') }}</p>
        </section>
    @endif
</x-layouts.admin>