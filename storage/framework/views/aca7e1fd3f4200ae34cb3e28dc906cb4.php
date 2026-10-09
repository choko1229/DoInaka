<footer class="site-footer">
    <div class="container">
        <div>
            
            <p class="t-aside t-muted" style="margin:0"><?php echo e(__('layout.footer_aside')); ?></p>
        </div>
        <?php if (isset($component)) { $__componentOriginal4c9b1e33e47ca4e88aa729e1c009542a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4c9b1e33e47ca4e88aa729e1c009542a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.theme-switch','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('theme-switch'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4c9b1e33e47ca4e88aa729e1c009542a)): ?>
<?php $attributes = $__attributesOriginal4c9b1e33e47ca4e88aa729e1c009542a; ?>
<?php unset($__attributesOriginal4c9b1e33e47ca4e88aa729e1c009542a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4c9b1e33e47ca4e88aa729e1c009542a)): ?>
<?php $component = $__componentOriginal4c9b1e33e47ca4e88aa729e1c009542a; ?>
<?php unset($__componentOriginal4c9b1e33e47ca4e88aa729e1c009542a); ?>
<?php endif; ?>
    </div>
</footer>
<?php /**PATH /var/www/html/resources/views/components/site-footer.blade.php ENDPATH**/ ?>