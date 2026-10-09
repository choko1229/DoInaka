<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['paginator']));

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

foreach (array_filter((['paginator']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php if($paginator->hasPages()): ?>
    <nav class="pager" aria-label="<?php echo e(__('public.pager')); ?>">
        <?php if($paginator->onFirstPage()): ?>
            <span class="chip" aria-disabled="true"><?php echo e(__('public.prev')); ?></span>
        <?php else: ?>
            <a class="chip" rel="prev" href="<?php echo e($paginator->previousPageUrl()); ?>"><?php echo e(__('public.prev')); ?></a>
        <?php endif; ?>
        <span class="t-small t-muted"><?php echo e($paginator->currentPage()); ?> / <?php echo e($paginator->lastPage()); ?></span>
        <?php if($paginator->hasMorePages()): ?>
            <a class="chip" rel="next" href="<?php echo e($paginator->nextPageUrl()); ?>"><?php echo e(__('public.next')); ?></a>
        <?php else: ?>
            <span class="chip" aria-disabled="true"><?php echo e(__('public.next')); ?></span>
        <?php endif; ?>
    </nav>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/components/pager.blade.php ENDPATH**/ ?>