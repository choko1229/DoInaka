<x-layouts.admin :title="__('masters.region_edit')" current="masters">
    <div class="page-head">
        <h1 class="t-h1">{{ __('masters.region_edit') }}</h1>
        <p class="t-small t-muted">{{ __('masters.lead') }}</p>
    </div>
    <p><a href="{{ route('admin.masters', ['tab' => 'regions']) }}">{{ __('masters.back') }}</a></p>

    <form method="post" action="{{ route('admin.masters.regions.update', $region) }}" class="grid-main-side">
        @csrf
        @method('put')
        <section class="card">
            <h2 class="t-h2">{{ $region->level->label() }}@if ($region->era) <span class="pill">{{ $region->era->label() }}</span>@endif</h2>
            <div class="field">
                <label for="name">{{ __('masters.field_name') }}</label>
                <input id="name" name="name" value="{{ old('name', $region->name) }}" required maxlength="100">
                @error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="name_kana">{{ __('masters.field_kana') }}</label>
                <input id="name_kana" name="name_kana" value="{{ old('name_kana', $region->name_kana) }}" maxlength="150">
            </div>
            <div class="field">
                <label for="slug">{{ __('masters.field_slug') }}</label>
                <input id="slug" name="slug" value="{{ old('slug', $region->slug) }}" required maxlength="100" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                <p class="t-small t-muted">{{ __('masters.field_slug_help') }}</p>
                @error('slug')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                <p class="t-small t-muted">{{ __('masters.url_preview') }}: {{ url('/'.$region->path().'/') }}</p>
            </div>

            <div class="field">
                <label for="official_url">{{ __('region.official_url') }}</label>
                <input id="official_url" name="official_url" type="url" value="{{ old('official_url', $region->official_url) }}" maxlength="500" placeholder="https://">
                <p class="t-small t-muted">{{ __('region.official_url_help') }}</p>
                @error('official_url')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>

            @if ($region->level === \App\Enums\RegionLevel::OldMunicipality)
                <div class="field">
                    <label for="parent_id">{{ __('masters.field_parent') }}</label>
                    <select id="parent_id" name="parent_id">
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((int) old('parent_id', $region->parent_id) === $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="former_parent_id">{{ __('masters.field_former_parent') }}</label>
                    <select id="former_parent_id" name="former_parent_id">
                        <option value="">—</option>
                        @foreach ($formerParents as $former)
                            <option value="{{ $former->id }}" @selected((int) old('former_parent_id', $region->former_parent_id) === $former->id)>{{ $former->name }}</option>
                        @endforeach
                    </select>
                </div>
                <fieldset class="field">
                    <legend>{{ __('masters.field_era') }}</legend>
                    @foreach (\App\Enums\EraTag::cases() as $era)
                        <label><input type="radio" name="era" value="{{ $era->value }}" @checked(old('era', $region->era?->value) === $era->value)> {{ $era->label() }}</label>
                    @endforeach
                </fieldset>
                <dl class="facts">
                    <dt>{{ __('masters.abolished') }}</dt><dd>{{ $region->abolished_on?->format('Y/m/d') }}</dd>
                    <dt>{{ __('masters.merged_into') }}</dt><dd>{{ $region->merged_into }}</dd>
                </dl>
                <p class="t-caption t-muted">{{ __('masters.region_info') }}</p>
            @endif
        </section>

        <aside class="card">
            <h2 class="t-h2">{{ __('masters.field_active') }}</h2>
            <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $region->is_active))><span>{{ __('masters.field_active') }}</span></label>
            <div class="field">
                <label for="reason">{{ __('masters.field_reason') }}</label>
                <input id="reason" name="reason" maxlength="200" value="{{ old('reason') }}">
                <p class="t-caption t-muted">{{ __('masters.field_reason_help') }}</p>
            </div>
            <x-button type="submit" variant="primary">{{ __('masters.save') }}</x-button>
            <p><a href="{{ route('admin.revisions', ['type' => 'region', 'id' => $region->id]) }}">{{ __('masters.history', ['count' => $revisionCount]) }}</a></p>
        </aside>
    </form>
</x-layouts.admin>