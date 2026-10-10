@props(['name' => 'region_id', 'selected' => null, 'id' => null, 'required' => true])
@php($groups = app(\App\Services\Content\RegionOptions::class)->grouped())
<select id="{{ $id ?? $name }}" name="{{ $name }}" @if ($required) required @endif>
    <option value="">—</option>
    @foreach ($groups as $group)
        <optgroup label="{{ $group['label'] }}">
            @foreach ($group['options'] as $regionId => $label)
                <option value="{{ $regionId }}" @selected((int) $selected === $regionId)>{{ $label }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>