<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['size' => 32, 'href' => '/']));

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

foreach (array_filter((['size' => 32, 'href' => '/']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<a class="logo" href="<?php echo e($href); ?>" style="font-size: <?php echo e((int) $size); ?>px" aria-label="<?php echo e(config('app.name')); ?>">
    <svg class="logo-mark" viewBox="0 0 32 32" aria-hidden="true"><path d="M3 26 L12 13 L17 19 L21 14 L29 26" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/><path d="M24 5 V26 M20 8 H28 M21 11 H27" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><path d="M28 8 Q31 11 32 10" fill="none" stroke="currentColor" stroke-width="1.25"/></svg>
    <b>ド田舎</b><i>.net</i>
</a>
<?php /**PATH /var/www/html/resources/views/components/logo.blade.php ENDPATH**/ ?>