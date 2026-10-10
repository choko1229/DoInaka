{{-- 都道府県 → 市区町村(初期値は香川県)。JS があれば、都道府県を変えたとき市区町村を取り直し、地図で指した場所からも自動で入れる --}}
<div class="field" data-region-picker data-api="{{ url('/api/v1/regions') }}" data-nearest="{{ url('/api/v1/regions/nearest') }}">
    <label for="pref_id">{{ __('submission.pref') }}</label>
    <select id="pref_id" data-pref>
        @foreach ($prefectures as $pref)
            <option value="{{ $pref->id }}" @selected((int) old('pref_id', $defaultPref?->id) === $pref->id)>{{ $pref->name }}</option>
        @endforeach
    </select>
    <label for="region_id">{{ __('submission.city') }}</label>
    <select id="region_id" name="region_id" data-city @if ($required ?? false) required @endif>
        <option value="">{{ $required ?? false ? '—' : __('submission.city_unknown') }}</option>
        @foreach ($cities as $city)
            <option value="{{ $city->id }}" @selected((int) old('region_id') === $city->id)>{{ $city->name }}</option>
        @endforeach
    </select>
    @error('region_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
</div>