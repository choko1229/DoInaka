<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'href',
    'title',
    'date' => null,
    'area' => null,
    'tags' => [],
    'status' => 'scheduled',
    'photo' => null,
    'alt' => '',
    'illust' => null,
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
    'href',
    'title',
    'date' => null,
    'area' => null,
    'tags' => [],
    'status' => 'scheduled',
    'photo' => null,
    'alt' => '',
    'illust' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<a class="event-card" href="<?php echo e($href); ?>">
    <div class="event-card-photo">
        <?php if($photo): ?>
            <img src="<?php echo e($photo); ?>" alt="<?php echo e($alt); ?>" loading="lazy" decoding="async">
        <?php elseif($illust): ?>
            <?php if (isset($component)) { $__componentOriginal0314955bad3fd7afd6cb4b423384bf3e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0314955bad3fd7afd6cb4b423384bf3e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.illust-image','data' => ['illust' => $illust,'alt' => '']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('illust-image'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['illust' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($illust),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0314955bad3fd7afd6cb4b423384bf3e)): ?>
<?php $attributes = $__attributesOriginal0314955bad3fd7afd6cb4b423384bf3e; ?>
<?php unset($__attributesOriginal0314955bad3fd7afd6cb4b423384bf3e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0314955bad3fd7afd6cb4b423384bf3e)): ?>
<?php $component = $__componentOriginal0314955bad3fd7afd6cb4b423384bf3e; ?>
<?php unset($__componentOriginal0314955bad3fd7afd6cb4b423384bf3e); ?>
<?php endif; ?>
            <span class="event-card-wanted"><?php echo e(__('illust.wanted')); ?></span>
        <?php endif; ?>
    </div>
    <div class="event-card-body">
        <?php if($date || $status !== 'scheduled'): ?>
            <div>
                <?php if($date): ?><span class="event-card-date"><?php echo e($date); ?></span><?php endif; ?>
                <?php if($status === 'ended'): ?><span class="event-card-status"><?php echo e(__('layout.status_ended')); ?></span><?php endif; ?>
                <?php if($status === 'cancelled'): ?><span class="event-card-status cancelled"><?php echo e(__('layout.status_cancelled')); ?></span><?php endif; ?>
                <?php if($status === 'postponed'): ?><span class="event-card-status postponed"><?php echo e(__('layout.status_postponed')); ?></span><?php endif; ?>
                <?php if($status === 'undecided'): ?><span class="event-card-status"><?php echo e(__('layout.status_undecided')); ?></span><?php endif; ?>
            </div>
        <?php endif; ?>
        <span class="event-card-title"><?php echo e($title); ?></span>
        <?php if($area): ?><span class="t-small t-muted"><?php echo e($area); ?></span><?php endif; ?>
        <?php if($tags !== []): ?>
            <span class="t-small t-muted">
                <?php $__currentLoopData = $tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><span><?php echo e($tag); ?></span><?php if(! $loop->last): ?> · <?php endif; ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </span>
        <?php endif; ?>
    </div>
</a>
<?php /**PATH /var/www/html/resources/views/components/event-card.blade.php ENDPATH**/ ?>