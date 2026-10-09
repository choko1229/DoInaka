<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['illust', 'variant' => \App\Enums\IllustVariant::Card, 'alt' => '', 'lazy' => true]));

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

foreach (array_filter((['illust', 'variant' => \App\Enums\IllustVariant::Card, 'alt' => '', 'lazy' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $resolver = app(\App\Services\Design\IllustUrlResolver::class);
    $url = $resolver->url($illust, $variant);
    // カードは 600px と 1200px を srcset で出し分ける(一覧では小さい方で足りる)
    $small = $variant === \App\Enums\IllustVariant::Card ? $resolver->url($illust, \App\Enums\IllustVariant::CardSm) : null;
?>

<?php if($url): ?>
    <img
        class="illust-image"
        src="<?php echo e($url); ?>"
        <?php if($small): ?> srcset="<?php echo e($small); ?> 600w, <?php echo e($url); ?> 1200w" sizes="(min-width: 768px) 400px, 100vw" <?php endif; ?>
        width="<?php echo e($variant->width()); ?>" height="<?php echo e($variant->height()); ?>"
        alt="<?php echo e($alt); ?>"
        <?php if($lazy): ?> loading="lazy" <?php endif; ?>
        decoding="async"
    >
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/components/illust-image.blade.php ENDPATH**/ ?>