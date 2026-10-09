<?php
    $links = app(\App\Services\Url\PublicLinks::class);
    $status = $event->displayStatus();
    $ended = $event->status->value === 'ended' || $status === 'ended';
?>
<?php if (isset($component)) { $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.public','data' => ['meta' => $meta,'pref' => $pref,'current' => 'events']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.public'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'pref' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pref),'current' => 'events']); ?>
    <article class="detail">
        <header>
            <p class="detail-labels">
                <?php if($event->category): ?><span class="pill"><?php echo e($event->category->name); ?></span><?php endif; ?>
                <?php if($status === 'cancelled'): ?><span class="pill pill-failed"><?php echo e(__('layout.status_cancelled')); ?></span><?php endif; ?>
                <?php if($event->is_postponed): ?><span class="pill pill-warning"><?php echo e(__('layout.status_postponed')); ?></span><?php endif; ?>
                <?php if($ended): ?><span class="pill"><?php echo e(__('public.ended')); ?></span><?php endif; ?>
            </p>
            <h1 class="t-display"><?php echo e($event->title); ?></h1>

            
            <section class="sources" aria-label="<?php echo e(__('public.sources')); ?>">
                <h2 class="t-h3"><?php echo e(__('public.sources')); ?></h2>
                <ul>
                    <?php $__currentLoopData = $event->sources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $source): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <span class="pill"><?php echo e($source->kind->label()); ?></span>
                            <?php if($source->url && preg_match('#^https?://#i', $source->url) === 1): ?>
                                <a href="<?php echo e($source->url); ?>" rel="nofollow noopener" target="_blank"><?php echo e($source->title ?: $source->url); ?></a>
                            <?php else: ?>
                                <span><?php echo e($source->title ?: $source->kind->label()); ?></span>
                            <?php endif; ?>
                            <?php if($source->kind === \App\Enums\EventSourceKind::Flyer && $source->media && $source->media->isProcessed()): ?>
                                <a href="<?php echo e(\App\Support\MediaUrl::large($source->media)); ?>" target="_blank" rel="noopener"><img class="source-flyer" src="<?php echo e(\App\Support\MediaUrl::small($source->media)); ?>" alt="<?php echo e($source->title ?: __('public.flyer_alt')); ?>" loading="lazy"></a>
                            <?php endif; ?>
                            <?php if($source->checked_at): ?><span class="t-small t-muted"><?php echo e(__('public.checked_at', ['date' => $source->checked_at->format('Y-m-d')])); ?></span><?php endif; ?>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </section>
        </header>

        <?php if($ended): ?>
            <p class="alert"><?php echo e(__('public.ended_notice')); ?>

                <?php if($nextEvent): ?><a href="<?php echo e($links->event($nextEvent)); ?>"><?php echo e(__('public.next_edition', ['date' => \App\Support\DateText::range($nextEvent)])); ?></a><?php endif; ?>
            </p>
        <?php endif; ?>

        <div class="detail-grid">
            <div class="detail-main">
                <section class="card">
                    <h2 class="t-h2"><?php echo e(__('public.schedule')); ?></h2>
                    <ul class="schedule">
                        <?php $__currentLoopData = $event->schedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-cancelled' => $s->is_cancelled]); ?>">
                                <strong><?php echo e(\App\Support\DateText::day($s->date)); ?></strong>
                                <?php if(\App\Support\DateText::time($s)): ?><span><?php echo e(\App\Support\DateText::time($s)); ?></span><?php endif; ?>
                                <?php if($s->is_cancelled): ?><span class="pill pill-failed"><?php echo e(__('layout.status_cancelled')); ?></span><?php endif; ?>
                                <?php if($s->note): ?><span class="t-small t-muted"><?php echo e($s->note); ?></span><?php endif; ?>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php if($event->schedules->isEmpty()): ?><li class="t-muted"><?php echo e(__('public.schedule_undecided')); ?></li><?php endif; ?>
                    </ul>
                    <dl class="facts">
                        <?php if($event->venue_name): ?><dt><?php echo e(__('public.venue')); ?></dt><dd><?php echo e($event->venue_name); ?></dd><?php endif; ?>
                        <?php if($event->address): ?><dt><?php echo e(__('public.address')); ?></dt><dd><?php echo e($event->address); ?></dd><?php endif; ?>
                        <?php if($event->fee): ?><dt><?php echo e(__('public.fee')); ?></dt><dd><?php echo e($event->fee); ?></dd><?php endif; ?>
                        <?php if($event->url && preg_match('#^https?://#i', $event->url) === 1): ?>
                            <dt><?php echo e(__('public.official_site')); ?></dt><dd><a href="<?php echo e($event->url); ?>" rel="nofollow noopener" target="_blank"><?php echo e($event->url); ?></a></dd>
                        <?php endif; ?>
                    </dl>
                    <?php if($event->lat !== null && $event->lng !== null): ?>
                        <div class="map" data-map data-lat="<?php echo e($event->lat); ?>" data-lng="<?php echo e($event->lng); ?>" data-title="<?php echo e($event->title); ?>" role="img" aria-label="<?php echo e(__('public.map_of', ['name' => $event->title])); ?>"></div>
                    <?php endif; ?>
                </section>

                <?php if($event->body): ?>
                    <section class="card prose"><h2 class="t-h2"><?php echo e(__('public.about_event')); ?></h2><p><?php echo nl2br(e($event->body)); ?></p></section>
                <?php endif; ?>

                <?php if($event->tags->isNotEmpty()): ?>
                    <p class="tags"><?php $__currentLoopData = $event->tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a class="chip" href="/<?php echo e($pref); ?>/events/?tag=<?php echo e(urlencode($tag->name)); ?>"><?php echo e($tag->name); ?></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></p>
                <?php endif; ?>

                <section class="card">
                    <h2 class="t-h2"><?php echo e(__('public.photos')); ?></h2>
                    <?php if (isset($component)) { $__componentOriginal57c28f5ad6af257e8b535dfbe6900ca5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal57c28f5ad6af257e8b535dfbe6900ca5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.media-gallery','data' => ['media' => $event->media,'title' => $event->title]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('media-gallery'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['media' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($event->media),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($event->title)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal57c28f5ad6af257e8b535dfbe6900ca5)): ?>
<?php $attributes = $__attributesOriginal57c28f5ad6af257e8b535dfbe6900ca5; ?>
<?php unset($__attributesOriginal57c28f5ad6af257e8b535dfbe6900ca5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal57c28f5ad6af257e8b535dfbe6900ca5)): ?>
<?php $component = $__componentOriginal57c28f5ad6af257e8b535dfbe6900ca5; ?>
<?php unset($__componentOriginal57c28f5ad6af257e8b535dfbe6900ca5); ?>
<?php endif; ?>
                    <p class="t-muted"><?php if($event->media->isEmpty()): ?><?php echo e(__('illust.wanted')); ?> — <?php endif; ?><a href="/post/photo/event/<?php echo e($event->id); ?>/"><?php echo e(__('public.post_photo')); ?></a></p>
                </section>

                <section class="card" id="comments">
                    <h2 class="t-h2"><?php echo e(__('public.comments')); ?></h2>
                    <?php $__empty_1 = true; $__currentLoopData = $comments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="comment">
                            <p class="t-small t-muted"><?php echo e($comment->user?->name ?? __('public.anonymous')); ?><?php if($comment->is_official): ?> · <?php echo e(__('public.official')); ?><?php endif; ?></p>
                            <p><?php echo nl2br(e($comment->body)); ?></p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="t-muted"><?php echo e(__('public.no_comments')); ?></p>
                    <?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal1a6e5077f335bc664c1e6423ce0001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1a6e5077f335bc664c1e6423ce0001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.comment-form','data' => ['type' => 'event','id' => $event->id]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('comment-form'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'event','id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($event->id)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.reaction-buttons','data' => ['type' => 'event','id' => $event->id,'favoriteCount' => \App\Models\Favorite::query()->where('favoritable_type', 'event')->where('favoritable_id', $event->id)->count(),'visitCount' => \App\Models\Visit::query()->where('visitable_type', 'event')->where('visitable_id', $event->id)->count()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('reaction-buttons'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'event','id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($event->id),'favorite-count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Models\Favorite::query()->where('favoritable_type', 'event')->where('favoritable_id', $event->id)->count()),'visit-count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Models\Visit::query()->where('visitable_type', 'event')->where('visitable_id', $event->id)->count())]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.share-buttons','data' => ['url' => $shareUrl,'title' => $event->title]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('share-buttons'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shareUrl),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($event->title)]); ?>
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
                <p class="t-small"><a href="/report/event/<?php echo e($event->id); ?>/"><?php echo e(__('public.report_error')); ?></a></p>
                <?php if($event->series): ?>
                    <p class="t-small"><a href="<?php echo e($links->series($event->series)); ?>"><?php echo e(__('public.series_all', ['title' => $event->series->title])); ?></a></p>
                <?php endif; ?>
            </aside>
        </div>

        <?php if($sameSeries->isNotEmpty()): ?>
            <section class="block"><h2 class="t-h1"><?php echo e(__('public.other_editions')); ?></h2>
                <div class="card-grid"><?php $__currentLoopData = $sameSeries->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (isset($component)) { $__componentOriginal50451f1e0b04fb740cb30a32f53f5734 = $component; } ?>
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
<?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
            </section>
        <?php endif; ?>
        <?php if($nearbyEvents->isNotEmpty()): ?>
            <section class="block"><h2 class="t-h1"><?php echo e(__('public.nearby_events')); ?></h2>
                <div class="card-grid"><?php $__currentLoopData = $nearbyEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (isset($component)) { $__componentOriginal50451f1e0b04fb740cb30a32f53f5734 = $component; } ?>
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
<?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
            </section>
        <?php endif; ?>
        <?php if($nearbySpots->isNotEmpty()): ?>
            <section class="block"><h2 class="t-h1"><?php echo e(__('public.nearby_spots')); ?></h2>
                <div class="card-grid"><?php $__currentLoopData = $nearbySpots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (isset($component)) { $__componentOriginal50451f1e0b04fb740cb30a32f53f5734 = $component; } ?>
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
<?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
            </section>
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/public/events/show.blade.php ENDPATH**/ ?>