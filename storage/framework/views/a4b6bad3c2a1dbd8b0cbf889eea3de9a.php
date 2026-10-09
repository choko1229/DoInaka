<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['url', 'title']));

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

foreach (array_filter((['url', 'title']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div class="share">
    <span class="t-small t-muted"><?php echo e(__('public.share')); ?></span>
    <a class="chip" rel="noopener" target="_blank" href="https://twitter.com/intent/tweet?<?php echo e(http_build_query(['url' => $url, 'text' => $title])); ?>">X</a>
    <a class="chip" rel="noopener" target="_blank" href="https://social-plugins.line.me/lineit/share?<?php echo e(http_build_query(['url' => $url])); ?>">LINE</a>
    <button class="chip" type="button" data-copy="<?php echo e($url); ?>"><?php echo e(__('public.copy_url')); ?></button>
    <details class="share-qr">
        <summary class="chip"><?php echo e(__('public.qr')); ?></summary>
        <div class="qr" role="img" aria-label="<?php echo e(__('public.qr_label')); ?>"><?php echo \App\Support\QrCode::svg($url); ?></div>
    </details>
</div><?php /**PATH /var/www/html/resources/views/components/share-buttons.blade.php ENDPATH**/ ?>