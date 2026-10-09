<?php if (isset($component)) { $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.public','data' => ['meta' => $meta,'pref' => $pref,'current' => 'spots']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.public'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'pref' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pref),'current' => 'spots']); ?>
    <article class="detail">
        <header>
            <p class="detail-labels"><?php if($spot->category): ?><span class="pill"><?php echo e($spot->category->name); ?></span><?php endif; ?></p>
            <h1 class="t-display"><?php echo e($spot->title); ?></h1>
        </header>
        <div class="detail-grid">
            <div class="detail-main">
                <section class="card">
                    <dl class="facts">
                        <?php if($spot->address): ?><dt><?php echo e(__('public.address')); ?></dt><dd><?php echo e($spot->address); ?></dd><?php endif; ?>
                        <?php if($spot->hours): ?><dt><?php echo e(__('public.hours')); ?></dt><dd><?php echo e($spot->hours); ?></dd><?php endif; ?>
                        <?php if($spot->access): ?><dt><?php echo e(__('public.access')); ?></dt><dd><?php echo e($spot->access); ?></dd><?php endif; ?>
                        <?php if($spot->url && preg_match('#^https?://#i', $spot->url) === 1): ?>
                            <dt><?php echo e(__('public.official_site')); ?></dt><dd><a href="<?php echo e($spot->url); ?>" rel="nofollow noopener" target="_blank"><?php echo e($spot->url); ?></a></dd>
                        <?php endif; ?>
                    </dl>
                    <?php if($spot->lat !== null && $spot->lng !== null): ?>
                        <div class="map" data-map data-lat="<?php echo e($spot->lat); ?>" data-lng="<?php echo e($spot->lng); ?>" data-title="<?php echo e($spot->title); ?>" role="img" aria-label="<?php echo e(__('public.map_of', ['name' => $spot->title])); ?>"></div>
                    <?php endif; ?>
                </section>
                <?php if($spot->media->isNotEmpty()): ?><section class="card"><?php if (isset($component)) { $__componentOriginal57c28f5ad6af257e8b535dfbe6900ca5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal57c28f5ad6af257e8b535dfbe6900ca5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.media-gallery','data' => ['media' => $spot->media,'title' => $spot->title]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('media-gallery'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['media' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($spot->media),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($spot->title)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal57c28f5ad6af257e8b535dfbe6900ca5)): ?>
<?php $attributes = $__attributesOriginal57c28f5ad6af257e8b535dfbe6900ca5; ?>
<?php unset($__attributesOriginal57c28f5ad6af257e8b535dfbe6900ca5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal57c28f5ad6af257e8b535dfbe6900ca5)): ?>
<?php $component = $__componentOriginal57c28f5ad6af257e8b535dfbe6900ca5; ?>
<?php unset($__componentOriginal57c28f5ad6af257e8b535dfbe6900ca5); ?>
<?php endif; ?></section><?php endif; ?>
                <?php if($spot->body): ?><section class="card prose"><p><?php echo nl2br(e($spot->body)); ?></p></section><?php endif; ?>
                <?php if($spot->tags->isNotEmpty()): ?>
                    <p class="tags"><?php $__currentLoopData = $spot->tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a class="chip" href="/<?php echo e($pref); ?>/spots/?tag=<?php echo e(urlencode($tag->name)); ?>"><?php echo e($tag->name); ?></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></p>
                <?php endif; ?>
                <section class="card" id="comments">
                    <h2 class="t-h2"><?php echo e(__('public.comments')); ?></h2>
                    <?php $__empty_1 = true; $__currentLoopData = $comments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="comment"><p class="t-small t-muted"><?php echo e($comment->user?->name ?? __('public.anonymous')); ?></p><p><?php echo nl2br(e($comment->body)); ?></p></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="t-muted"><?php echo e(__('public.no_comments')); ?></p>
                    <?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal1a6e5077f335bc664c1e6423ce0001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1a6e5077f335bc664c1e6423ce0001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.comment-form','data' => ['type' => 'spot','id' => $spot->id]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('comment-form'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'spot','id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($spot->id)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1a6e5077f335bc664c1e6423ce0001f6)): ?>
<?php $attributes = $__attributesOriginal1a6e5077f335bc664c1e6423ce0001f6; ?>
<?php unset($__attributesOriginal1a6e5077f335bc664c1e6423ce0001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1a6e5077f335bc664c1e6423ce0001f6)): ?>
<?php $component = $__componentOriginal1a6e5077f335bc664c1e6423ce0001f6; ?>
<?php unset($__componentOriginal1a6e5077f335bc664c1e6423ce0001f6); ?>
<?php endif; ?>
                </section>
            </div>
            <aside class="detail-side">
                <?php if (isset($component)) { $__componentOriginal7b8db765393db675d52fd0783d8ffd4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7b8db765393db675d52fd0783d8ffd4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.reaction-buttons','data' => ['type' => 'spot','id' => $spot->id,'favoriteCount' => \App\Models\Favorite::query()->where('favoritable_type', 'spot')->where('favoritable_id', $spot->id)->count(),'visitCount' => \App\Models\Visit::query()->where('visitable_type', 'spot')->where('visitable_id', $spot->id)->count()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('reaction-buttons'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'spot','id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($spot->id),'favorite-count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Models\Favorite::query()->where('favoritable_type', 'spot')->where('favoritable_id', $spot->id)->count()),'visit-count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Models\Visit::query()->where('visitable_type', 'spot')->where('visitable_id', $spot->id)->count())]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7b8db765393db675d52fd0783d8ffd4f)): ?>
<?php $attributes = $__attributesOriginal7b8db765393db675d52fd0783d8ffd4f; ?>
<?php unset($__attributesOriginal7b8db765393db675d52fd0783d8ffd4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7b8db765393db675d52fd0783d8ffd4f)): ?>
<?php $component = $__componentOriginal7b8db765393db675d52fd0783d8ffd4f; ?>
<?php unset($__componentOriginal7b8db765393db675d52fd0783d8ffd4f); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginale74326542b72aa0b690ae5e4be9fcbaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale74326542b72aa0b690ae5e4be9fcbaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.share-buttons','data' => ['url' => $shareUrl,'title' => $spot->title]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('share-buttons'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shareUrl),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($spot->title)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale74326542b72aa0b690ae5e4be9fcbaf)): ?>
<?php $attributes = $__attributesOriginale74326542b72aa0b690ae5e4be9fcbaf; ?>
<?php unset($__attributesOriginale74326542b72aa0b690ae5e4be9fcbaf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale74326542b72aa0b690ae5e4be9fcbaf)): ?>
<?php $component = $__componentOriginale74326542b72aa0b690ae5e4be9fcbaf; ?>
<?php unset($__componentOriginale74326542b72aa0b690ae5e4be9fcbaf); ?>
<?php endif; ?>
                <p class="t-small"><a href="/report/spot/<?php echo e($spot->id); ?>/"><?php echo e(__('public.report_error')); ?></a></p>
                <p class="t-small"><a href="/post/photo/spot/<?php echo e($spot->id); ?>/"><?php echo e(__('public.post_photo')); ?></a></p>
            </aside>
        </div>
        <?php if($nearbyEvents->isNotEmpty()): ?>
            <section class="block"><h2 class="t-h1"><?php echo e(__('public.nearby_events')); ?></h2><div class="card-grid"><?php $__currentLoopData = $nearbyEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (isset($component)) { $__componentOriginal50451f1e0b04fb740cb30a32f53f5734 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal50451f1e0b04fb740cb30a32f53f5734 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.content-card','data' => ['item' => $e]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('content-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['item' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($e)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal50451f1e0b04fb740cb30a32f53f5734)): ?>
<?php $attributes = $__attributesOriginal50451f1e0b04fb740cb30a32f53f5734; ?>
<?php unset($__attributesOriginal50451f1e0b04fb740cb30a32f53f5734); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal50451f1e0b04fb740cb30a32f53f5734)): ?>
<?php $component = $__componentOriginal50451f1e0b04fb740cb30a32f53f5734; ?>
<?php unset($__componentOriginal50451f1e0b04fb740cb30a32f53f5734); ?>
<?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div></section>
        <?php endif; ?>
        <?php if($nearbySpots->isNotEmpty()): ?>
            <section class="block"><h2 class="t-h1"><?php echo e(__('public.nearby_spots')); ?></h2><div class="card-grid"><?php $__currentLoopData = $nearbySpots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (isset($component)) { $__componentOriginal50451f1e0b04fb740cb30a32f53f5734 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal50451f1e0b04fb740cb30a32f53f5734 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.content-card','data' => ['item' => $s]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('content-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['item' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($s)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal50451f1e0b04fb740cb30a32f53f5734)): ?>
<?php $attributes = $__attributesOriginal50451f1e0b04fb740cb30a32f53f5734; ?>
<?php unset($__attributesOriginal50451f1e0b04fb740cb30a32f53f5734); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal50451f1e0b04fb740cb30a32f53f5734)): ?>
<?php $component = $__componentOriginal50451f1e0b04fb740cb30a32f53f5734; ?>
<?php unset($__componentOriginal50451f1e0b04fb740cb30a32f53f5734); ?>
<?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div></section>
        <?php endif; ?>
    </article>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $attributes = $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $component = $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/public/spots/show.blade.php ENDPATH**/ ?>