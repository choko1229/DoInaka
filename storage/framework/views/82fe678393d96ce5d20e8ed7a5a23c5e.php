<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => null,
    'current' => null,
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
    'current' => null,
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.base','data' => ['title' => $title ? $title.' | '.__('layout.admin') : __('layout.admin'),'noindex' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.base'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($title ? $title.' | '.__('layout.admin') : __('layout.admin')),'noindex' => true]); ?>
    <a class="visually-hidden" href="#main"><?php echo e(__('layout.skip_to_main')); ?></a>
    <div class="admin-shell">
        <nav class="admin-nav" aria-label="<?php echo e(__('layout.admin')); ?>">
            <div class="admin-brand">
                <?php if (isset($component)) { $__componentOriginal987d96ec78ed1cf75b349e2e5981978f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal987d96ec78ed1cf75b349e2e5981978f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.logo','data' => ['size' => 24,'href' => route('admin.dashboard')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 24,'href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.dashboard'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal987d96ec78ed1cf75b349e2e5981978f)): ?>
<?php $attributes = $__attributesOriginal987d96ec78ed1cf75b349e2e5981978f; ?>
<?php unset($__attributesOriginal987d96ec78ed1cf75b349e2e5981978f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal987d96ec78ed1cf75b349e2e5981978f)): ?>
<?php $component = $__componentOriginal987d96ec78ed1cf75b349e2e5981978f; ?>
<?php unset($__componentOriginal987d96ec78ed1cf75b349e2e5981978f); ?>
<?php endif; ?>
                <span class="admin-badge"><?php echo e(__('layout.admin_badge')); ?></span>
            </div>
            <ul>
                
                <li><a href="<?php echo e(route('admin.dashboard')); ?>" <?php if($current === 'dashboard'): ?> aria-current="page" <?php endif; ?>><?php echo e(__('layout.admin_dashboard')); ?></a></li>
                <li><a href="<?php echo e(route('admin.update')); ?>" <?php if($current === 'update'): ?> aria-current="page" <?php endif; ?>><?php echo e(__('admin.update')); ?></a></li>
            </ul>
            <?php if(auth()->guard()->check()): ?>
                <div class="admin-account">
                    <p class="t-small t-muted" style="margin:0"><?php echo e(auth()->user()?->name); ?></p>
                    <form method="post" action="<?php echo e(route('logout')); ?>"><?php echo csrf_field(); ?><button type="submit" class="link-button"><?php echo e(__('auth.account_logout')); ?></button></form>
                    <form method="post" action="<?php echo e(route('admin.two-factor.reset')); ?>" onsubmit="return confirm(<?php echo \Illuminate\Support\Js::from(__('auth.account_reset_confirm'))->toHtml() ?>)"><?php echo csrf_field(); ?><button type="submit" class="link-button"><?php echo e(__('auth.account_reset_two_factor')); ?></button></form>
                </div>
            <?php endif; ?>
        </nav>
        <main id="main" class="admin-main">
            <?php if(session('status')): ?>
                <p class="alert alert-success" role="status"><?php echo e(session('status')); ?></p>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <p class="alert alert-danger" role="alert"><?php echo e(session('error')); ?></p>
            <?php endif; ?>
            <?php echo e($slot); ?>

        </main>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald4c772c02301431d3253f64117700596)): ?>
<?php $attributes = $__attributesOriginald4c772c02301431d3253f64117700596; ?>
<?php unset($__attributesOriginald4c772c02301431d3253f64117700596); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald4c772c02301431d3253f64117700596)): ?>
<?php $component = $__componentOriginald4c772c02301431d3253f64117700596; ?>
<?php unset($__componentOriginald4c772c02301431d3253f64117700596); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/components/layouts/admin.blade.php ENDPATH**/ ?>