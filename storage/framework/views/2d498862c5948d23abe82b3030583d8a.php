<?php ($current = $themeContext->preference->value); ?>
<div class="theme-switch" role="group" aria-label="<?php echo e(__('layout.theme_switch')); ?>">
    <?php $__currentLoopData = ['auto' => 'theme_auto', 'day' => 'theme_day', 'night' => 'theme_night']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button type="button" data-theme-option="<?php echo e($value); ?>" aria-pressed="<?php echo e($current === $value ? 'true' : 'false'); ?>"><?php echo e(__('layout.'.$label)); ?></button>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH /var/www/html/resources/views/components/theme-switch.blade.php ENDPATH**/ ?>