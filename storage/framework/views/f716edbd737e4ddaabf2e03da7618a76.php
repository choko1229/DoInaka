<?php if (isset($component)) { $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.public','data' => ['meta' => $meta,'pref' => $pref,'current' => 'articles']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.public'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'pref' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pref),'current' => 'articles']); ?>
    <article class="detail detail-narrow">
        <h1 class="t-display"><?php echo e($article->title); ?></h1>
        <p class="t-small t-muted"><?php echo e($article->region->name); ?><?php if($article->published_at): ?> · <?php echo e($article->published_at->format('Y-m-d')); ?><?php endif; ?></p>
        <?php if($article->body): ?><div class="prose"><p><?php echo nl2br(e($article->body)); ?></p></div><?php endif; ?>
        <?php if($article->tags->isNotEmpty()): ?>
            <p class="tags"><?php $__currentLoopData = $article->tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a class="chip" href="/<?php echo e($pref); ?>/articles/?tag=<?php echo e(urlencode($tag->name)); ?>"><?php echo e($tag->name); ?></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></p>
        <?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal7b8db765393db675d52fd0783d8ffd4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7b8db765393db675d52fd0783d8ffd4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.reaction-buttons','data' => ['type' => 'article','id' => $article->id,'favoriteCount' => \App\Models\Favorite::query()->where('favoritable_type', 'article')->where('favoritable_id', $article->id)->count()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('reaction-buttons'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'article','id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($article->id),'favorite-count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Models\Favorite::query()->where('favoritable_type', 'article')->where('favoritable_id', $article->id)->count())]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.share-buttons','data' => ['url' => $shareUrl,'title' => $article->title]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('share-buttons'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shareUrl),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($article->title)]); ?>
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/public/articles/show.blade.php ENDPATH**/ ?>