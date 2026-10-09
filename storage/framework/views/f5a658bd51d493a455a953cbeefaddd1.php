<?php if (isset($component)) { $__componentOriginala4dba0b979c83f75f6062075e1003056 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala4dba0b979c83f75f6062075e1003056 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin-auth','data' => ['step' => 1]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin-auth'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['step' => 1]); ?>
    <h1 class="t-h1"><?php echo e(__('auth.admin_login_title')); ?></h1>
    <p><?php echo e(__('auth.admin_login_lead')); ?></p>
    <?php if (isset($component)) { $__componentOriginal5c54a6d4d676c1c26a467c6a9c275c37 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c54a6d4d676c1c26a467c6a9c275c37 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.google-button','data' => ['href' => route('auth.google', ['admin' => 1]),'class' => 'google-btn-block']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('google-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('auth.google', ['admin' => 1])),'class' => 'google-btn-block']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c54a6d4d676c1c26a467c6a9c275c37)): ?>
<?php $attributes = $__attributesOriginal5c54a6d4d676c1c26a467c6a9c275c37; ?>
<?php unset($__attributesOriginal5c54a6d4d676c1c26a467c6a9c275c37); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c54a6d4d676c1c26a467c6a9c275c37)): ?>
<?php $component = $__componentOriginal5c54a6d4d676c1c26a467c6a9c275c37; ?>
<?php unset($__componentOriginal5c54a6d4d676c1c26a467c6a9c275c37); ?>
<?php endif; ?>
    <?php $__errorArgs = ['google'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <p class="alert alert-danger" role="alert"><?php echo e($message); ?></p>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala4dba0b979c83f75f6062075e1003056)): ?>
<?php $attributes = $__attributesOriginala4dba0b979c83f75f6062075e1003056; ?>
<?php unset($__attributesOriginala4dba0b979c83f75f6062075e1003056); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala4dba0b979c83f75f6062075e1003056)): ?>
<?php $component = $__componentOriginala4dba0b979c83f75f6062075e1003056; ?>
<?php unset($__componentOriginala4dba0b979c83f75f6062075e1003056); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/auth/login.blade.php ENDPATH**/ ?>