<?php
    $links = app(\App\Services\Url\PublicLinks::class);
?>
<?php if (isset($component)) { $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.public','data' => ['meta' => $meta,'pref' => $pref,'compact' => ! $isPref]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.public'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'pref' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pref),'compact' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(! $isPref)]); ?>
    <?php
        $resolver = app(\App\Services\Design\IllustUrlResolver::class);
        $illust = new \App\Data\Illust($isPref ? \App\Enums\Place::Island : \App\Enums\Place::Field, $themeContext->season, $themeContext->theme);
        $wide = $resolver->url($illust, \App\Enums\IllustVariant::Wide);
        $card = $resolver->url($illust, \App\Enums\IllustVariant::Card);
    ?>
    
    <?php if($wide && $card): ?>
        <picture class="region-art" aria-hidden="true">
            <source media="(min-width: 768px)" srcset="<?php echo e($wide); ?>">
            <img src="<?php echo e($card); ?>" alt="" width="1200" height="900" decoding="async">
        </picture>
    <?php endif; ?>
    <header class="region-head">
        <h1 class="t-display"><?php echo e($region->name); ?></h1>
        <?php if($region->name_kana): ?><p class="t-small t-muted"><?php echo e($region->name_kana); ?></p><?php endif; ?>
        <?php if($region->era): ?>
            <p class="alert"><?php echo e($region->era->label()); ?><?php if($region->abolished_on): ?>(<?php echo e(__('public.abolished', ['date' => $region->abolished_on->format('Y年n月j日')])); ?>)<?php endif; ?> <?php if($region->merged_into): ?><?php echo e(__('public.merged_into', ['name' => $region->merged_into])); ?><?php endif; ?></p>
        <?php endif; ?>
    </header>

    <section class="card">
        <h2 class="t-h2"><?php echo e(__('public.region_intro')); ?></h2>
        <?php if($region->intro_body): ?>
            <p><?php echo nl2br(e($region->intro_body)); ?></p>
            <?php if(is_array($region->intro_sources) && $region->intro_sources !== []): ?>
                <p class="t-small t-muted"><?php echo e(__('public.region_sources')); ?>:
                    <?php $__currentLoopData = $region->intro_sources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $source): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php ($srcUrl = is_array($source) ? (string) ($source['url'] ?? '') : (string) $source); ?>
                        <?php if(preg_match('#^https?://#i', $srcUrl) === 1): ?><a href="<?php echo e($srcUrl); ?>" rel="nofollow noopener" target="_blank"><?php echo e(is_array($source) ? ($source['title'] ?? $srcUrl) : $srcUrl); ?></a><?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p class="t-muted"><?php echo e(__('public.region_preparing')); ?></p>
        <?php endif; ?>
    </section>

    <?php if($children->isNotEmpty() || $alsoChildren->isNotEmpty() || $former->isNotEmpty()): ?>
        <section class="block">
            <h2 class="t-h1"><?php echo e($isPref ? __('public.cities') : __('public.areas')); ?></h2>
            <p class="hero-chips">
                <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a class="chip" href="<?php echo e($links->region($child)); ?>"><?php echo e($child->name); ?></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </p>
            <?php if($alsoChildren->isNotEmpty()): ?>
                <h3 class="t-h3"><?php echo e(__('public.also_part')); ?></h3>
                <p class="hero-chips"><?php $__currentLoopData = $alsoChildren; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a class="chip" href="<?php echo e($links->region($child)); ?>"><?php echo e($child->name); ?></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></p>
            <?php endif; ?>
            <?php if($former->isNotEmpty()): ?>
                <h3 class="t-h3"><?php echo e(__('public.former_towns')); ?></h3>
                <p class="hero-chips"><?php $__currentLoopData = $former; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a class="chip" href="<?php echo e($links->region($child)); ?>"><?php echo e($child->name); ?></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="block">
        <div class="block-head"><h2 class="t-h1"><?php echo e(__('public.region_events', ['region' => $region->name])); ?></h2><a href="/<?php echo e($pref); ?>/events/"><?php echo e(__('public.see_all')); ?></a></div>
        <?php if($events->isEmpty()): ?><p class="t-muted"><?php echo e(__('public.no_events')); ?></p>
        <?php else: ?><div class="card-grid"><?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (isset($component)) { $__componentOriginal50451f1e0b04fb740cb30a32f53f5734 = $component; } ?>
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
<?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div><?php endif; ?>
    </section>

    <?php if($spots->isNotEmpty()): ?>
        <section class="block">
            <div class="block-head"><h2 class="t-h1"><?php echo e(__('public.region_spots', ['region' => $region->name])); ?></h2><a href="/<?php echo e($pref); ?>/spots/"><?php echo e(__('public.see_all')); ?></a></div>
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
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $attributes = $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $component = $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/public/region.blade.php ENDPATH**/ ?>