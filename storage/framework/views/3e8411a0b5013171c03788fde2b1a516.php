<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => null,
    'description' => null,
    'noindex' => false,
    'compact' => true,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title' => null,
    'description' => null,
    'noindex' => false,
    'compact' => true,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php if (isset($component)) { $__componentOriginald4c772c02301431d3253f64117700596 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c772c02301431d3253f64117700596 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.base','data' => ['title' => $title,'description' => $description,'noindex' => $noindex]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.base'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($title),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($description),'noindex' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($noindex)]); ?>
    <a class="visually-hidden" href="#main"><?php echo e(__('layout.skip_to_main')); ?></a>
    <?php if (isset($component)) { $__componentOriginal1cd9664925ed155579777e29557b9757 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cd9664925ed155579777e29557b9757 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sky-header','data' => ['compact' => $compact]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sky-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['compact' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($compact)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cd9664925ed155579777e29557b9757)): ?>
<?php $attributes = $__attributesOriginal1cd9664925ed155579777e29557b9757; ?>
<?php unset($__attributesOriginal1cd9664925ed155579777e29557b9757); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cd9664925ed155579777e29557b9757)): ?>
<?php $component = $__componentOriginal1cd9664925ed155579777e29557b9757; ?>
<?php unset($__componentOriginal1cd9664925ed155579777e29557b9757); ?>
<?php endif; ?>
    <main id="main">
        <div class="container">
            <?php echo e($slot); ?>

        </div>
    </main>
    <?php if (isset($component)) { $__componentOriginal222c87a019257fb1d70ae0ff46ab02e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal222c87a019257fb1d70ae0ff46ab02e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-footer','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal222c87a019257fb1d70ae0ff46ab02e1)): ?>
<?php $attributes = $__attributesOriginal222c87a019257fb1d70ae0ff46ab02e1; ?>
<?php unset($__attributesOriginal222c87a019257fb1d70ae0ff46ab02e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal222c87a019257fb1d70ae0ff46ab02e1)): ?>
<?php $component = $__componentOriginal222c87a019257fb1d70ae0ff46ab02e1; ?>
<?php unset($__componentOriginal222c87a019257fb1d70ae0ff46ab02e1); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald4c772c02301431d3253f64117700596)): ?>
<?php $attributes = $__attributesOriginald4c772c02301431d3253f64117700596; ?>
<?php unset($__attributesOriginald4c772c02301431d3253f64117700596); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald4c772c02301431d3253f64117700596)): ?>
<?php $component = $__componentOriginald4c772c02301431d3253f64117700596; ?>
<?php unset($__componentOriginald4c772c02301431d3253f64117700596); ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/components/layouts/public.blade.php ENDPATH**/ ?>