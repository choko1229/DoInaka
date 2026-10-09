<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'code' => null,
    'title',
    'aside' => null,
    'errorId' => null,
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
    'code' => null,
    'title',
    'aside' => null,
    'errorId' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<section class="empty-state">
    <div class="empty-state-art" aria-hidden="true">
        <svg viewBox="0 0 340 220" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="84" cy="64" r="16"/>
            <rect x="148" y="30" width="122" height="64" rx="8"/>
            <path d="M166 52 H252 M166 70 H226 M209 94 V190"/>
            <path d="M20 190 H340"/>
            <path d="M52 190 C110 130 190 138 250 128 C268 126 280 122 290 118"/>
        </svg>
    </div>
    <div>
        <?php if($code): ?><p class="empty-state-code t-small" style="margin:0"><?php echo e($code); ?></p><?php endif; ?>
        <h1 class="t-display"><?php echo e($title); ?></h1>
        <?php if($aside): ?><p class="t-aside" style="margin:var(--space-2) 0 0"><?php echo e($aside); ?></p><?php endif; ?>
        <p class="t-small t-muted" style="margin:var(--space-3) 0 0"><?php echo e($slot); ?></p>
        <?php if($errorId): ?>
            <p class="empty-state-id t-small t-muted"><?php echo e(__('errors.error_id')); ?>: <code><?php echo e($errorId); ?></code></p>
        <?php endif; ?>
        <div class="empty-state-actions">
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'primary','href' => url('/')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(url('/'))]); ?><?php echo e(__('errors.back_to_top')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
        </div>
    </div>
</section>
<?php /**PATH /var/www/html/resources/views/components/empty-state.blade.php ENDPATH**/ ?>