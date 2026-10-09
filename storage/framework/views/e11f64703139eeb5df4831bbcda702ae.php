<div class="field">
    <label for="photos"><?php echo e($label ?? __('submission.photos')); ?></label>
    <input id="photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif" data-photos data-max="<?php echo e($maxPhotos); ?>">
    <p class="t-small t-muted"><?php echo e(__('submission.photos_hint', ['max' => $maxPhotos, 'mb' => app(\App\Services\Setting\SettingsService::class)->int(\App\Enums\SettingKey::UploadMaxMb)])); ?></p>
    <?php $__errorArgs = ['photos'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<label class="check-row">
    <input type="checkbox" name="rights_agreed" value="1" <?php if(old('rights_agreed')): echo 'checked'; endif; ?>>
    <span><?php echo e(__('submission.rights_label')); ?></span>
</label>
<?php $__errorArgs = ['rights_agreed'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php /**PATH /var/www/html/resources/views/public/post/partials/photos.blade.php ENDPATH**/ ?>