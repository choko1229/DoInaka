<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('admin.dashboard'),'current' => 'dashboard']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('admin.dashboard')),'current' => 'dashboard']); ?>
    <h1 class="t-h1"><?php echo e(__('admin.dashboard')); ?></h1>
    <?php if (isset($component)) { $__componentOriginalcc2f0af263c948f4371398f1ec3bb808 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcc2f0af263c948f4371398f1ec3bb808 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-warnings','data' => ['warnings' => $warnings]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-warnings'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['warnings' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($warnings)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcc2f0af263c948f4371398f1ec3bb808)): ?>
<?php $attributes = $__attributesOriginalcc2f0af263c948f4371398f1ec3bb808; ?>
<?php unset($__attributesOriginalcc2f0af263c948f4371398f1ec3bb808); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcc2f0af263c948f4371398f1ec3bb808)): ?>
<?php $component = $__componentOriginalcc2f0af263c948f4371398f1ec3bb808; ?>
<?php unset($__componentOriginalcc2f0af263c948f4371398f1ec3bb808); ?>
<?php endif; ?>
    <p class="t-muted"><?php echo e(__('admin.dashboard_lead')); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $attributes = $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $component = $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>