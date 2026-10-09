<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => null,
    'description' => null,
    'noindex' => false,
    'meta' => null,
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
    'meta' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $siteName = config('app.name');
    if ($meta instanceof \App\Support\PageMeta) {
        $title = $meta->title;
        $description = $meta->description;
        $noindex = $meta->noindex;
    }
    $pageTitle = $title ? $title.' | '.$siteName : $siteName;
?>
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" data-theme="<?php echo e($themeContext->theme->value); ?>" data-season="<?php echo e($themeContext->season->value); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?></title>
    <?php if($description): ?>
        <meta name="description" content="<?php echo e($description); ?>">
    <?php endif; ?>
    <?php if($noindex): ?>
        <meta name="robots" content="noindex, follow">
    <?php endif; ?>
    <?php if($meta instanceof \App\Support\PageMeta): ?>
        <?php if($meta->canonical): ?>
            <link rel="canonical" href="<?php echo e($meta->canonical); ?>">
            <meta property="og:url" content="<?php echo e($meta->canonical); ?>">
        <?php endif; ?>
        <meta property="og:site_name" content="<?php echo e($siteName); ?>">
        <meta property="og:type" content="<?php echo e($meta->ogType); ?>">
        <meta property="og:title" content="<?php echo e($pageTitle); ?>">
        <?php if($description): ?><meta property="og:description" content="<?php echo e($description); ?>"><?php endif; ?>
        <?php if($meta->image): ?><meta property="og:image" content="<?php echo e($meta->image); ?>"><?php endif; ?>
        <meta name="twitter:card" content="<?php echo e($meta->image ? 'summary_large_image' : 'summary'); ?>">
        <?php $__currentLoopData = $meta->jsonLd; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $block): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            
            <script type="application/ld+json"><?php echo json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
    <?php echo $__env->yieldPushContent('head'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body>
    <?php echo e($slot); ?>

</body>
</html><?php /**PATH /var/www/html/resources/views/components/layouts/base.blade.php ENDPATH**/ ?>