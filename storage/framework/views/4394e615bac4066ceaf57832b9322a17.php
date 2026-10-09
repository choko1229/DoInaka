<?php if (isset($component)) { $__componentOriginalc7b6b0f5d16f8a605a52718a155b5029 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.install','data' => ['step' => 4]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.install'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['step' => 4]); ?>
    <section class="card">
        <h2 class="t-h2"><?php echo e(__('install.done_title')); ?></h2>
        <p><?php echo e(__('install.done_body', ['name' => $name])); ?></p>
        <p class="t-small t-muted"><?php echo e(__('install.done_note')); ?></p>
        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'primary','href' => url('/')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(url('/'))]); ?><?php echo e(__('install.done_link')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
    </section>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029)): ?>
<?php $attributes = $__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029; ?>
<?php unset($__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc7b6b0f5d16f8a605a52718a155b5029)): ?>
<?php $component = $__componentOriginalc7b6b0f5d16f8a605a52718a155b5029; ?>
<?php unset($__componentOriginalc7b6b0f5d16f8a605a52718a155b5029); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/install/done.blade.php ENDPATH**/ ?>