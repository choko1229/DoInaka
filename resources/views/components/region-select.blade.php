@props(['name' => 'region_id', 'selected' => null, 'id' => null, 'required' => true])
@php($groups = app(\App\Services\Content\RegionOptions::class)->grouped())
<div data-region-tree="{{ json_encode(app(\App\Services\Content\RegionOptions::class)->tree(), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) }}" data-labels="{{ json_encode(['pref' => __('content.region_pref'), 'city' => __('content.region_city'), 'old' => __('content.region_old'), 'none' => __('content.region_none')], JSON_UNESCAPED_UNICODE) }}">
<select id="{{ $id ?? $name }}" name="{{ $name }}" data-region-source @if ($required) required @endif>
    <option value="">—</option>
    @foreach ($groups as $group)
        <optgroup label="{{ $group['label'] }}">
            @foreach ($group['options'] as $regionId => $label)
                <option value="{{ $regionId }}" @selected((int) $selected === $regionId)>{{ $label }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>
</div>
