<div class="field">
    <label for="photos">{{ $label ?? __('submission.photos') }}</label>
    <input id="photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif" data-photos data-max="{{ $maxPhotos }}">
    <p class="t-small t-muted">{{ __('submission.photos_hint', ['max' => $maxPhotos, 'mb' => app(\App\Services\Setting\SettingsService::class)->int(\App\Enums\SettingKey::UploadMaxMb)]) }}</p>
    @error('photos')<p class="field-error" role="alert">{{ $message }}</p>@enderror
</div>
<label class="check-row">
    <input type="checkbox" name="rights_agreed" value="1" @checked(old('rights_agreed'))>
    <span>{{ __('submission.rights_label') }}</span>
</label>
@error('rights_agreed')<p class="field-error" role="alert">{{ $message }}</p>@enderror