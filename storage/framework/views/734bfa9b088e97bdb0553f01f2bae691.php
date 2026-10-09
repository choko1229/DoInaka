
<div class="field" data-region-picker data-api="<?php echo e(url('/api/v1/regions')); ?>" data-nearest="<?php echo e(url('/api/v1/regions/nearest')); ?>">
    <label for="pref_id"><?php echo e(__('submission.pref')); ?></label>
    <select id="pref_id" data-pref>
        <?php $__currentLoopData = $prefectures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pref): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($pref->id); ?>" <?php if((int) old('pref_id', $defaultPref?->id) === $pref->id): echo 'selected'; endif; ?>><?php echo e($pref->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <label for="region_id"><?php echo e(__('submission.city')); ?></label>
    <select id="region_id" name="region_id" data-city <?php if($required ?? false): ?> required <?php endif; ?>>
        <option value=""><?php echo e($required ?? false ? '—' : __('submission.city_unknown')); ?></option>
        <?php $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($city->id); ?>" <?php if((int) old('region_id') === $city->id): echo 'selected'; endif; ?>><?php echo e($city->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php $__errorArgs = ['region_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div><?php /**PATH /var/www/html/resources/views/public/post/partials/region.blade.php ENDPATH**/ ?>