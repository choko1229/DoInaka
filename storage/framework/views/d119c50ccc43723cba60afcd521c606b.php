<?php if (isset($component)) { $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.public','data' => ['meta' => $meta,'compact' => false,'pref' => $pref]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.public'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'compact' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'pref' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pref)]); ?>
    <section class="hero">
        <h1 class="t-display"><?php echo e(__('layout.tagline')); ?></h1>
        <p class="t-aside"><?php echo e(__('layout.footer_aside')); ?></p>
        <form class="hero-search" action="/<?php echo e($pref); ?>/events/" method="get" role="search">
            <label class="visually-hidden" for="hero-q"><?php echo e(__('public.search_label')); ?></label>
            <input id="hero-q" name="q" type="search" maxlength="100" placeholder="<?php echo e(__('public.search_placeholder')); ?>">
            <button class="btn btn-primary" type="submit"><?php echo e(__('public.search')); ?></button>
        </form>
        <p class="hero-chips">
            <a class="chip" href="/<?php echo e($pref); ?>/events/weekend/"><?php echo e(__('public.weekend')); ?></a>
            <a class="chip" href="/<?php echo e($pref); ?>/events/?when=today"><?php echo e(__('public.today')); ?></a>
            <a class="chip" href="/<?php echo e($pref); ?>/map/"><?php echo e(__('public.nav_map')); ?></a>
        </p>
    </section>

    <?php if($weekend !== []): ?>
        <section class="block">
            <div class="block-head"><h2 class="t-h1"><?php echo e(__('public.this_weekend')); ?></h2><a href="/<?php echo e($pref); ?>/events/weekend/"><?php echo e(__('public.see_all')); ?></a></div>
            <div class="card-grid"><?php $__currentLoopData = $weekend; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (isset($component)) { $__componentOriginal50451f1e0b04fb740cb30a32f53f5734 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal50451f1e0b04fb740cb30a32f53f5734 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.content-card','data' => ['item' => $event]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('content-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['item' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($event)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal50451f1e0b04fb740cb30a32f53f5734)): ?>
<?php $attributes = $__attributesOriginal50451f1e0b04fb740cb30a32f53f5734; ?>
<?php unset($__attributesOriginal50451f1e0b04fb740cb30a32f53f5734); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal50451f1e0b04fb740cb30a32f53f5734)): ?>
<?php $component = $__componentOriginal50451f1e0b04fb740cb30a32f53f5734; ?>
<?php unset($__componentOriginal50451f1e0b04fb740cb30a32f53f5734); ?>
<?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
        </section>
    <?php endif; ?>

    <section class="block">
        <div class="block-head"><h2 class="t-h1"><?php echo e(__('public.upcoming')); ?></h2><a href="/<?php echo e($pref); ?>/events/"><?php echo e(__('public.see_all')); ?></a></div>
        <?php if($upcoming === []): ?>
            <p class="t-muted"><?php echo e(__('public.no_events')); ?></p>
        <?php else: ?>
            <div class="card-grid"><?php $__currentLoopData = $upcoming; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (isset($component)) { $__componentOriginal50451f1e0b04fb740cb30a32f53f5734 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal50451f1e0b04fb740cb30a32f53f5734 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.content-card','data' => ['item' => $event]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('content-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['item' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($event)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal50451f1e0b04fb740cb30a32f53f5734)): ?>
<?php $attributes = $__attributesOriginal50451f1e0b04fb740cb30a32f53f5734; ?>
<?php unset($__attributesOriginal50451f1e0b04fb740cb30a32f53f5734); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal50451f1e0b04fb740cb30a32f53f5734)): ?>
<?php $component = $__componentOriginal50451f1e0b04fb740cb30a32f53f5734; ?>
<?php unset($__componentOriginal50451f1e0b04fb740cb30a32f53f5734); ?>
<?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
        <?php endif; ?>
    </section>

    <?php if($spots !== []): ?>
        <section class="block">
            <div class="block-head"><h2 class="t-h1"><?php echo e(__('public.popular_spots')); ?></h2><a href="/<?php echo e($pref); ?>/spots/"><?php echo e(__('public.see_all')); ?></a></div>
            <div class="card-grid"><?php $__currentLoopData = $spots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $spot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (isset($component)) { $__componentOriginal50451f1e0b04fb740cb30a32f53f5734 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal50451f1e0b04fb740cb30a32f53f5734 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.content-card','data' => ['item' => $spot]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('content-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['item' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($spot)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal50451f1e0b04fb740cb30a32f53f5734)): ?>
<?php $attributes = $__attributesOriginal50451f1e0b04fb740cb30a32f53f5734; ?>
<?php unset($__attributesOriginal50451f1e0b04fb740cb30a32f53f5734); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal50451f1e0b04fb740cb30a32f53f5734)): ?>
<?php $component = $__componentOriginal50451f1e0b04fb740cb30a32f53f5734; ?>
<?php unset($__componentOriginal50451f1e0b04fb740cb30a32f53f5734); ?>
<?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
        </section>
    <?php endif; ?>

    <section class="block">
        <h2 class="t-h1"><?php echo e(__('public.regions')); ?></h2>
        <p class="hero-chips">
            <?php $__currentLoopData = $prefectures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a class="chip" href="<?php echo e($links->region($p)); ?>"><?php echo e($p->name); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </p>
    </section>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $attributes = $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $component = $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/public/home.blade.php ENDPATH**/ ?>