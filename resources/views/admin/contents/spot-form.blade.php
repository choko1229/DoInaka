@php($state = old('state', $spot->exists ? ($spot->is_published ? 'published' : 'draft') : 'draft'))
<x-layouts.admin :title="__('content.spot_edit')" current="contents">
    <div class="page-head">
        <h1 class="t-h1">{{ $spot->exists ? __('content.spot_edit') : __('content.spot_add') }}</h1>
        <p class="t-small t-muted">{{ __('content.revision_note') }}</p>
    </div>
    <p class="t-small"><a href="{{ route('admin.contents', ['tab' => 'spot']) }}">{{ __('content.back_contents') }}</a> @if ($spot->exists)/ {{ $spot->title }}@endif</p>

    <form method="post" action="{{ $spot->exists ? route('admin.spots.update', $spot) : route('admin.spots.store') }}" class="grid-main-side">
        @csrf
        @if ($spot->exists) @method('put') @endif
        <div class="form-sections">
            <section class="card">
                <h2 class="t-h2">{{ __('content.section_basic') }}</h2>
                <div class="field"><label for="title">{{ __('content.field_title') }} <span class="req">{{ __('submission.required') }}</span></label><input id="title" name="title" value="{{ old('title', $spot->title) }}" required maxlength="200">@error('title')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                <div class="field-pair">
                    <div class="field"><label for="category_id">{{ __('content.field_category') }}</label>
                        <select id="category_id" name="category_id"><option value="">—</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((int) old('category_id', $spot->category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                    <div class="field"><label for="best_season">{{ __('content.field_best_season') }}</label>
                        <select id="best_season" name="best_season"><option value="">—</option>@foreach (['spring', 'summer', 'autumn', 'winter'] as $season)<option value="{{ $season }}" @selected(old('best_season', $spot->best_season) === $season)>{{ __('content.season_'.$season) }}</option>@endforeach</select></div>
                </div>
            </section>

            <section class="card">
                <h2 class="t-h2">{{ __('content.section_place') }}</h2>
                <div class="field"><label for="region_id">{{ __('content.field_region') }}</label><x-region-select :selected="old('region_id', $spot->region_id)" />@error('region_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                <div class="field"><label for="address">{{ __('content.field_address') }}</label><input id="address" name="address" value="{{ old('address', $spot->address) }}" maxlength="300"><p class="t-caption t-muted">{{ __('content.address_help') }}</p></div>
                <div class="field-pair">
                    <div class="field"><label for="lat">{{ __('content.field_lat') }}</label><input id="lat" name="lat" inputmode="decimal" value="{{ old('lat', $spot->lat) }}"></div>
                    <div class="field"><label for="lng">{{ __('content.field_lng') }}</label><input id="lng" name="lng" inputmode="decimal" value="{{ old('lng', $spot->lng) }}"></div>
                </div>
            </section>

            <section class="card">
                <h2 class="t-h2">{{ __('content.section_body') }}</h2>
                <div class="field"><label for="body">{{ __('content.section_body') }}</label><textarea id="body" name="body" rows="6" maxlength="20000">{{ old('body', $spot->body) }}</textarea><p class="t-caption t-muted">{{ __('content.body_help') }}</p></div>
                <div class="field"><label for="tags">{{ __('content.field_tags') }}</label><input id="tags" name="tags" value="{{ old('tags', $tagsText) }}" maxlength="300"><p class="t-caption t-muted">{{ __('content.field_tags_help') }}</p></div>
            </section>

            <section class="card">
                <h2 class="t-h2">{{ __('content.section_info') }}</h2>
                <div class="field-pair">
                    <div class="field"><label for="hours">{{ __('content.field_hours') }}</label><input id="hours" name="hours" value="{{ old('hours', $spot->hours) }}" maxlength="300"></div>
                    <div class="field"><label for="closed_days">{{ __('content.field_closed') }}</label><input id="closed_days" name="closed_days" value="{{ old('closed_days', $spot->closed_days) }}" maxlength="200"></div>
                    <div class="field"><label for="fee">{{ __('content.field_fee') }}</label><input id="fee" name="fee" value="{{ old('fee', $spot->fee) }}" maxlength="200"></div>
                    <div class="field"><label for="parking">{{ __('content.field_parking') }}</label><input id="parking" name="parking" value="{{ old('parking', $spot->parking) }}" maxlength="200"></div>
                    <div class="field"><label for="payment">{{ __('content.field_payment') }}</label><input id="payment" name="payment" value="{{ old('payment', $spot->payment) }}" maxlength="200"></div>
                    <div class="field"><label for="toilet">{{ __('content.field_toilet') }}</label><input id="toilet" name="toilet" value="{{ old('toilet', $spot->toilet) }}" maxlength="200"></div>
                    <div class="field"><label for="access">{{ __('content.field_access') }}</label><input id="access" name="access" value="{{ old('access', $spot->access) }}" maxlength="300"></div>
                    <div class="field"><label for="contact">{{ __('content.field_contact') }}</label><input id="contact" name="contact" value="{{ old('contact', $spot->contact) }}" maxlength="200"><p class="t-caption t-muted">{{ __('content.contact_help') }}</p></div>
                </div>
                <div class="field"><label for="url">{{ __('content.field_url') }}</label><input id="url" name="url" type="url" value="{{ old('url', $spot->url) }}" maxlength="500"></div>
            </section>

            <section class="card">
                <h2 class="t-h2">{{ __('content.section_caution') }}</h2>
                <label class="check-row"><input type="checkbox" name="private_land_checked" value="1" @checked(old('private_land_checked', $spot->private_land_checked))><span>{{ __('content.private_land_checked') }}</span></label>
                <div class="field"><label for="caution_text">{{ __('content.field_caution') }}</label><textarea id="caution_text" name="caution_text" rows="3" maxlength="500">{{ old('caution_text', $spot->caution_text) }}</textarea></div>
                <div class="field"><label for="slug">{{ __('content.field_slug') }}</label><input id="slug" name="slug" value="{{ old('slug', $spot->slug) }}" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*"></div>
            </section>
        </div>
        <aside class="card side-card">
            <h2 class="t-h2">{{ __('content.publish_and_save') }}</h2>
            <fieldset class="field"><legend>{{ __('content.field_state') }}</legend>
                <label><input type="radio" name="state" value="published" @checked($state === 'published')> {{ __('content.state_published') }}</label>
                <label><input type="radio" name="state" value="draft" @checked($state !== 'published')> {{ __('content.state_unpublished') }}</label></fieldset>
            <div class="field"><label for="reason">{{ __('content.field_reason') }}</label><input id="reason" name="reason" maxlength="200" value="{{ old('reason') }}"><p class="t-caption t-muted">{{ __('content.field_reason_help') }}</p></div>
            <x-button type="submit" variant="primary">{{ __('content.save') }}</x-button>
            @if ($spot->exists)
                <p class="t-small"><a href="{{ route('admin.revisions', ['type' => 'spot', 'id' => $spot->id]) }}">{{ __('content.history_count', ['count' => $revisionCount]) }}</a><br><a href="{{ app(\App\Services\Url\PublicLinks::class)->spot($spot) }}">{{ __('content.view_public') }}</a></p>
            @endif
        </aside>
    </form>
    @if ($spot->exists)
        <form method="post" action="{{ route('admin.spots.destroy', $spot) }}" data-confirm="{{ __('content.delete_confirm') }}">@csrf @method('delete')<x-button type="submit">{{ __('content.delete_mistake') }}</x-button></form>
    @endif
</x-layouts.admin>