<div class="repeat-row">
    <div class="field">
        <label>{{ __('content.field_source_kind') }}</label>
        <select name="sources[{{ $i }}][kind]">
            @foreach (\App\Enums\EventSourceKind::cases() as $kind)
                <option value="{{ $kind->value }}" @selected(($row['kind'] ?? 'url') === $kind->value)>{{ $kind->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>URL</label><input type="url" name="sources[{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" maxlength="500"></div>
    <div class="field"><label>{{ __('content.field_source_title') }}</label><input name="sources[{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" maxlength="200"></div>
    <div class="field"><label>{{ __('content.field_checked_at') }}</label><input type="date" name="sources[{{ $i }}][checked_at]" value="{{ $row['checked_at'] ?? '' }}"></div>
    <label class="check-row"><input type="checkbox" name="sources[{{ $i }}][is_official]" value="1" @checked(! empty($row['is_official']))><span>{{ __('content.field_official') }}</span></label>
    <button type="button" class="link-button" data-repeat-remove>{{ __('content.remove_row') }}</button>
</div>