<?php if (isset($component)) { $__componentOriginala4dba0b979c83f75f6062075e1003056 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala4dba0b979c83f75f6062075e1003056 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin-auth','data' => ['step' => 4]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin-auth'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['step' => 4]); ?>
    <h1 class="t-h1"><?php echo e(__('auth.codes_title')); ?></h1>
    <p><?php echo e(__('auth.codes_lead')); ?></p>
    <ul class="codes" id="recovery-codes">
        <?php $__currentLoopData = $codes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo e($code); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <div class="button-row">
        <button type="button" class="btn" data-download="#recovery-codes" data-filename="<?php echo e(__('auth.codes_filename')); ?>"><?php echo e(__('auth.codes_download')); ?></button>
        <button type="button" class="btn" data-copy="#recovery-codes" data-copied="<?php echo e(__('auth.copied')); ?>"><?php echo e(__('auth.codes_copy')); ?></button>
    </div>
    <label class="check-row">
        <input type="checkbox" id="codes-saved" data-enables="#codes-continue">
        <span><?php echo e(__('auth.codes_saved')); ?></span>
    </label>
    <a id="codes-continue" class="btn btn-primary btn-block" href="<?php echo e(url('/admin')); ?>" aria-disabled="true" tabindex="-1"><?php echo e(__('auth.codes_continue')); ?></a>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala4dba0b979c83f75f6062075e1003056)): ?>
<?php $attributes = $__attributesOriginala4dba0b979c83f75f6062075e1003056; ?>
<?php unset($__attributesOriginala4dba0b979c83f75f6062075e1003056); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala4dba0b979c83f75f6062075e1003056)): ?>
<?php $component = $__componentOriginala4dba0b979c83f75f6062075e1003056; ?>
<?php unset($__componentOriginala4dba0b979c83f75f6062075e1003056); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/auth/codes.blade.php ENDPATH**/ ?>