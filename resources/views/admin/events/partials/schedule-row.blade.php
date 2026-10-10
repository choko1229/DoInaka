<div class="repeat-row">
    <div class="field"><label>{{ __('content.col_date') }}</label><input type="date" name="schedules[{{ $i }}][date]" value="{{ $row['date'] ?? '' }}"></div>
    <div class="field"><label>{{ __('content.field_start') }}</label><input type="time" name="schedules[{{ $i }}][start_time]" value="{{ $row['start_time'] ?? '' }}"></div>
    <div class="field"><label>{{ __('content.field_end') }}</label><input type="time" name="schedules[{{ $i }}][end_time]" value="{{ $row['end_time'] ?? '' }}"></div>
    <div class="field"><label>{{ __('content.col_note') }}</label><input name="schedules[{{ $i }}][note]" value="{{ $row['note'] ?? '' }}" maxlength="200"></div>
    <label class="check-row"><input type="checkbox" name="schedules[{{ $i }}][is_cancelled]" value="1" @checked(! empty($row['is_cancelled']))><span>{{ __('content.display_cancelled') }}</span></label>
    <button type="button" class="link-button" data-repeat-remove>{{ __('content.remove_row') }}</button>
</div>