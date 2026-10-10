@php
    $meta = new \App\Support\PageMeta(title: __('submission.form_title', ['type' => __('enums.submission_type.'.$type->value)]), noindex: true);
    $field = fn (string $name) => $errors->first($name);
@endphp
<x-layouts.public :meta="$meta" current="post">
    <h1 class="t-display">{{ __('enums.submission_type.'.$type->value) }}</h1>
    <p>{{ __('submission.lead_'.$type->value) }}</p>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="t-strong" style="margin:0">{{ __('submission.errors_title') }}</p>
            <ul class="t-small">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif

    <form class="card container-narrow" method="post" action="/post/{{ $type->value }}/" enctype="multipart/form-data" novalidate>
        @csrf

        @if ($type === \App\Enums\SubmissionType::Tip)
            <div class="field">
                <label for="source_url">{{ __('submission.tip_url') }}</label>
                <input id="source_url" type="url" name="source_url" maxlength="500" value="{{ old('source_url') }}" placeholder="https://" aria-describedby="source-hint">
                <p id="source-hint" class="t-small t-muted">{{ __('submission.tip_url_hint') }}</p>
                @error('source_url')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            @include('public.post.partials.photos', ['label' => __('submission.tip_photos')])
            @include('public.post.partials.region', ['required' => false])
            <div class="field">
                <label for="note">{{ __('submission.tip_note') }}</label>
                <textarea id="note" name="note" maxlength="500" rows="3">{{ old('note') }}</textarea>
                <p class="t-small t-muted">{{ __('submission.tip_note_hint') }}</p>
                @error('note')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
        @else
            @include('public.post.partials.region', ['required' => true])
            <div class="field">
                <label for="title">{{ __('submission.title') }}</label>
                <input id="title" type="text" name="title" maxlength="200" value="{{ old('title') }}" required>
                @error('title')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="body">{{ __('submission.body') }}</label>
                <textarea id="body" name="body" maxlength="{{ $type === \App\Enums\SubmissionType::Article ? 20000 : 5000 }}" rows="{{ $type === \App\Enums\SubmissionType::Article ? 12 : 6 }}" required>{{ old('body') }}</textarea>
                <p class="t-small t-muted">{{ __('submission.body_hint') }}</p>
                @error('body')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            @if ($type === \App\Enums\SubmissionType::Spot)
                <div class="field">
                    <label for="category_id">{{ __('submission.category') }}</label>
                    <select id="category_id" name="category_id">
                        <option value="">—</option>
                        @foreach ($categories as $category)<option value="{{ $category->id }}" @selected((int) old('category_id') === $category->id)>{{ $category->name }}</option>@endforeach
                    </select>
                </div>
                <div class="field"><label for="address">{{ __('submission.address') }}</label><input id="address" type="text" name="address" maxlength="300" value="{{ old('address') }}"></div>
                <div class="field">
                    <p class="t-strong" style="margin:0">{{ __('submission.location') }}</p>
                    <p class="t-small t-muted">{{ __('submission.location_hint') }}</p>
                    <div class="map" data-map-picker role="application" aria-label="{{ __('submission.location') }}"></div>
                    <input type="hidden" name="lat" value="{{ old('lat') }}" data-lat>
                    <input type="hidden" name="lng" value="{{ old('lng') }}" data-lng>
                    @error('lat')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="field"><label for="hours">{{ __('submission.hours') }}</label><input id="hours" type="text" name="hours" maxlength="300" value="{{ old('hours') }}"></div>
                <div class="field"><label for="access">{{ __('submission.access') }}</label><input id="access" type="text" name="access" maxlength="500" value="{{ old('access') }}"></div>
                <div class="field"><label for="url">{{ __('submission.url') }}</label><input id="url" type="url" name="url" maxlength="500" value="{{ old('url') }}" placeholder="https://">@error('url')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            @endif
            <div class="field"><label for="tags">{{ __('submission.tags') }}</label><input id="tags" type="text" name="tags" maxlength="200" value="{{ old('tags') }}"><p class="t-small t-muted">{{ __('submission.tags_hint') }}</p></div>
            @include('public.post.partials.photos')
        @endif

        <x-turnstile />
        <x-consent :overseas="true" />
        <button class="btn btn-primary" type="submit">{{ __('submission.send') }}</button>
    </form>
</x-layouts.public>