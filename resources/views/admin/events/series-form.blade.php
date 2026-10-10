<x-layouts.admin :title="__('content.series_edit')" current="events">
    <div class="page-head">
        <h1 class="t-h1">{{ $series->exists ? __('content.series_edit') : __('content.series_add') }}</h1>
        <p class="t-small t-muted">{{ __('content.revision_note') }}</p>
    </div>
    <p><a href="{{ route('admin.events') }}">{{ __('content.back_events') }}</a></p>

    <form method="post" action="{{ $series->exists ? route('admin.series.update', $series) : route('admin.series.store') }}" class="grid-main-side">
        @csrf
        @if ($series->exists) @method('put') @endif
        <section class="card">
            <div class="field">
                <label for="title">{{ __('content.field_series_title') }}</label>
                <input id="title" name="title" value="{{ old('title', $series->title) }}" required maxlength="200">
                @error('title')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="region_id">{{ __('content.field_region') }}</label>
                <x-region-select :selected="old('region_id', $series->region_id)" />
                @error('region_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="category_id">{{ __('content.field_category') }}</label>
                <select id="category_id" name="category_id">
                    <option value="">—</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('category_id', $series->category_id) === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <fieldset class="field">
                <legend>{{ __('content.field_recurrence') }}</legend>
                @foreach (\App\Enums\Recurrence::cases() as $r)
                    <label><input type="radio" name="recurrence" value="{{ $r->value }}" @checked(old('recurrence', $series->recurrence->value) === $r->value)> {{ $r->label() }}</label>
                @endforeach
            </fieldset>
            <div class="field">
                <label for="summary">{{ __('content.field_summary') }}</label>
                <textarea id="summary" name="summary" maxlength="2000">{{ old('summary', $series->summary) }}</textarea>
            </div>
            <div class="field">
                <label for="slug">{{ __('content.field_slug') }}</label>
                <input id="slug" name="slug" value="{{ old('slug', $series->slug) }}" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*">
            </div>
        </section>
        <aside class="card">
            <div class="field">
                <label for="reason">{{ __('content.field_reason') }}</label>
                <input id="reason" name="reason" maxlength="200" value="{{ old('reason') }}">
                <p class="t-caption t-muted">{{ __('content.field_reason_help') }}</p>
            </div>
            <x-button type="submit" variant="primary">{{ __('content.save') }}</x-button>
            @if ($series->exists)
                <p><a href="{{ route('admin.revisions', ['type' => 'series', 'id' => $series->id]) }}">{{ __('content.history') }}</a></p>
                <p><a class="btn btn-sm" href="{{ route('admin.events.create', ['series' => $series->id]) }}">{{ __('content.event_add') }}</a></p>
                <h2 class="t-h3">{{ __('content.events_of', ['title' => $series->title]) }}</h2>
                <ul class="t-small">
                    @foreach ($series->events as $e)
                        <li><a href="{{ route('admin.events.edit', $e) }}">{{ $e->schedules->first()?->date?->format('Y/m/d') ?? __('content.none') }}</a> {{ __('content.display_'.$e->displayStatus()) }}</li>
                    @endforeach
                </ul>
            @endif
        </aside>
    </form>
</x-layouts.admin>