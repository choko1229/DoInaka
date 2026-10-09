<?php if (isset($component)) { $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.public','data' => ['meta' => $meta,'pref' => $pref,'current' => 'map']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.public'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'pref' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pref),'current' => 'map']); ?>
    <h1 class="t-h1"><?php echo e(__('public.map_title', ['region' => $region->name])); ?></h1>
    <p class="t-small t-muted"><?php echo e(__('public.map_legend')); ?></p>
    <div class="map map-large" data-map data-pins='<?php echo json_encode($pins, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG, 512) ?>' data-lat="<?php echo e($center['lat']); ?>" data-lng="<?php echo e($center['lng']); ?>" data-zoom="10" role="application" aria-label="<?php echo e(__('public.nav_map')); ?>"></div>
    <noscript><p><?php echo e(__('public.map_noscript')); ?></p></noscript>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $attributes = $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $component = $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/public/map.blade.php ENDPATH**/ ?>