<?php ($meta = new \App\Support\PageMeta(title: __('submission.done_title'), noindex: true)); ?>
<?php if (isset($component)) { $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.public','data' => ['meta' => $meta,'current' => 'post']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.public'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'current' => 'post']); ?>
    <section class="card container-narrow">
        <h1 class="t-h1"><?php echo e(__('submission.done_title')); ?></h1>
        <p><?php echo e(__('submission.done_lead')); ?></p>
        <p class="t-small t-muted"><?php echo e(__('submission.receipt_no')); ?></p>
        <p class="receipt"><code><?php echo e($receiptNo); ?></code></p>
        <p class="t-small"><?php echo e(__('submission.done_note')); ?></p>
        <p><a class="btn" href="/"><?php echo e(__('submission.back_to_top')); ?></a></p>
    </section>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $attributes = $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $component = $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/public/post/done.blade.php ENDPATH**/ ?>