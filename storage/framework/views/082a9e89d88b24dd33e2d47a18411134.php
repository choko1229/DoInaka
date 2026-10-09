<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['type', 'id', 'favoriteCount' => 0, 'visitCount' => 0]));

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

foreach (array_filter((['type', 'id', 'favoriteCount' => 0, 'visitCount' => 0]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="reactions">
    <form method="post" action="<?php echo e(url("/api/v1/favorites/{$type}/{$id}")); ?>" data-reaction>
        <?php echo csrf_field(); ?>
        <button class="btn btn-sm" type="submit"><?php echo e(__('public.favorite')); ?> <span data-count><?php echo e($favoriteCount); ?></span></button>
    </form>
    <form method="post" action="<?php echo e(url("/api/v1/visits/{$type}/{$id}")); ?>" data-reaction>
        <?php echo csrf_field(); ?>
        <button class="btn btn-sm" type="submit"><?php echo e(__('public.visited')); ?> <span data-count><?php echo e($visitCount); ?></span></button>
    </form>
</div><?php /**PATH /var/www/html/resources/views/components/reaction-buttons.blade.php ENDPATH**/ ?>