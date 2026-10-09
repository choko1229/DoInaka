<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['media', 'title' => '']));

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

foreach (array_filter((['media', 'title' => '']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if($media->isNotEmpty()): ?>
    <div class="gallery">
        <?php $__currentLoopData = $media; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <figure>
                <a href="<?php echo e(\App\Support\MediaUrl::large($m)); ?>" target="_blank" rel="noopener">
                    <img src="<?php echo e(\App\Support\MediaUrl::medium($m)); ?>" srcset="<?php echo e(\App\Support\MediaUrl::srcset($m)); ?>" sizes="(min-width: 768px) 400px, 100vw" width="<?php echo e($m->width); ?>" height="<?php echo e($m->height); ?>" alt="<?php echo e($m->alt ?: $title); ?>" loading="lazy" decoding="async">
                </a>
                <figcaption class="t-small t-muted"><?php echo e(__('public.photo_credit', ['name' => $m->credit ?: __('public.provided_photo')])); ?></figcaption>
            </figure>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/components/media-gallery.blade.php ENDPATH**/ ?>