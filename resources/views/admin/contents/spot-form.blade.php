@php($state = old('state', $spot->exists ? ($spot->is_published ? 'published' : 'draft') : 'draft'))
<x-layouts.admin :title="__('content.spot_edit')" current="contents">
    <div class="page-head">
        <h1 class="t-h1">{{ $spot->exists ? __('content.spot_edit') : __('content.spot_add') }}</h1>
        <p class="t-small t-muted">{{ __('content.revision_note') }}</p>
    </div>
    <p><a href="{{ route('admin.contents', ['tab' => 'spot']) }}">{{ __('content.back_contents') }}</a></p>

    <form method="post" action="{{ $spot->exists ? route('admin.spots.update', $spot) : route('admin.spots.store') }}" class="grid-main-side">
        @csrf
        @if ($spot->exists) @method('put') @endif
        <section class="card">
            <div class="field"><label for="title">{{ __('content.field_title') }}</label><input id="title" name="title" value="{{ old('title', $spot->title) }}" required maxlength="200">@error('title')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="category_id">{{ __('content.field_category') }}</label>
                <select id="category_id" name="category_id"><option value="">—</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((int) old('category_id', $spot->category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></div>
            <div class="field"><label for="region_id">{{ __('content.field_region') }}</label><x-region-select :selected="old('region_id', $spot->region_id)" />@error('region_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="address">{{ __('content.field_address') }}</label><input id="address" name="address" value="{{ old('address', $spot->address) }}" maxlength="300"></div>
            <div class="repeat-row">
                <div class="field"><label for="lat">{{ __('content.field_lat') }}</label><input id="lat" name="lat" inputmode="decimal" value="{{ old('lat', $spot->lat) }}"></div>
                <div class="field"><label for="lng">{{ __('content.field_lng') }}</label><input id="lng" name="lng" inputmode="decimal" value="{{ old('lng', $spot->lng) }}"></div>
            </div>
            <div class="field"><label for="hours">{{ __('content.field_hours') }}</label><input id="hours" name="hours" value="{{ old('hours', $spot->hours) }}" maxlength="300"></div>
            <div class="field"><label for="access">{{ __('content.field_access') }}</label><input id="access" name="access" value="{{ old('access', $spot->access) }}" maxlength="300"></div>
            <div class="field"><label for="url">{{ __('content.field_url') }}</label><input id="url" name="url" type="url" value="{{ old('url', $spot->url) }}" maxlength="500"></div>
            <div class="field"><label for="body">{{ __('content.field_body') }}</label><textarea id="body" name="body" maxlength="20000">{{ old('body', $spot->body) }}</textarea></div>
            <div class="field"><label for="tags">{{ __('content.field_tags') }}</label><input id="tags" name="tags" value="{{ old('tags', $tagsText) }}" maxlength="300"><p class="t-caption t-muted">{{ __('content.field_tags_help') }}</p></div>
            <div class="field"><label for="slug">{{ __('content.field_slug') }}</label><input id="slug" name="slug" value="{{ old('slug', $spot->slug) }}" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*"></div>
        </section>
        <aside class="card">
            <fieldset class="field"><legend>{{ __('content.field_state') }}</legend>
                <label><input type="radio" name="state" value="published" @checked($state === 'published')> {{ __('content.state_published') }}</label>
                <label><input type="radio" name="state" value="draft" @checked($state !== 'published')> {{ __('content.state_draft') }}</label></fieldset>
            <div class="field"><label for="reason">{{ __('content.field_reason') }}</label><input id="reason" name="reason" maxlength="200" value="{{ old('reason') }}"><p class="t-caption t-muted">{{ __('content.field_reason_help') }}</p></div>
            <x-button type="submit" variant="primary">{{ __('content.save') }}</x-button>
            @if ($spot->exists)
                <p><a href="{{ route('admin.revisions', ['type' => 'spot', 'id' => $spot->id]) }}">{{ __('content.history_count', ['count' => $revisionCount]) }}</a></p>
            @endif
        </aside>
    </form>
    @if ($spot->exists)
        <form method="post" action="{{ route('admin.spots.destroy', $spot) }}" onsubmit="return confirm(@js(__('content.delete_confirm')))">@csrf @method('delete')<x-button type="submit">{{ __('content.delete_mistake') }}</x-button></form>
    @endif
</x-layouts.admin>