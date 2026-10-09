<?php ($today = now()->setTimezone('Asia/Tokyo')->toDateString()); ?>
<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('content.events_title'),'current' => 'events']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('content.events_title')),'current' => 'events']); ?>
    <div class="page-head">
        <h1 class="t-h1"><?php echo e(__('content.events_title')); ?></h1>
        <p class="t-small t-muted"><?php echo e(__('content.events_lead')); ?></p>
    </div>

    <form method="get" action="<?php echo e(route('admin.events')); ?>" class="inline-form">
        <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="<?php echo e(__('content.search_placeholder')); ?>" aria-label="<?php echo e(__('content.search_placeholder')); ?>">
        <?php $__currentLoopData = ['all', 'upcoming', 'no_next', 'unpublished']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a class="chip" href="<?php echo e(route('admin.events', array_filter(['filter' => $f === 'all' ? null : $f, 'q' => $q ?: null]))); ?>" <?php if($filter === $f): ?> aria-current="page" <?php endif; ?>><?php echo e(__('content.filter_'.$f)); ?><?php if($f === 'all'): ?> <?php echo e($counts['all']); ?><?php elseif($f === 'unpublished'): ?> <?php echo e($counts['unpublished']); ?><?php endif; ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => route('admin.series.create'),'variant' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.series.create')),'variant' => 'primary']); ?><?php echo e(__('content.series_add')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?php echo e(__('content.col_series')); ?></th><th><?php echo e(__('content.col_next')); ?></th><th><?php echo e(__('content.col_events')); ?></th><th></th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $series; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <strong><?php echo e($item->title); ?></strong><br>
                            <span class="t-small t-muted"><?php echo e($item->region->name); ?><?php if($item->category): ?> ・ <?php echo e($item->category->name); ?><?php endif; ?> ・ <?php echo e($item->recurrence->label()); ?></span>
                        </td>
                        <td><?php echo e(isset($nextDates[$item->id]) ? \Illuminate\Support\Carbon::parse($nextDates[$item->id])->isoFormat('M/D(ddd)') : __('content.none')); ?></td>
                        <td><?php echo e(__('content.events_count', ['count' => $item->events_count])); ?></td>
                        <td class="actions">
                            <a class="btn btn-sm" href="<?php echo e(route('admin.events', array_filter(['series' => $item->id, 'filter' => $filter === 'all' ? null : $filter]))); ?>"><?php echo e(__('content.events_show')); ?></a>
                            <a class="btn btn-sm" href="<?php echo e(route('admin.series.edit', $item)); ?>"><?php echo e(__('content.edit')); ?></a>
                            <a class="btn btn-sm" href="<?php echo e(route('admin.events.create', ['series' => $item->id])); ?>"><?php echo e(__('content.event_add')); ?></a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="t-muted"><?php echo e(__('content.empty')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo e($series->links()); ?>


    <?php if($selected): ?>
        <section class="card" aria-labelledby="detail-title">
            <h2 class="t-h2" id="detail-title"><?php echo e(__('content.events_of', ['title' => $selected->title])); ?></h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th><?php echo e(__('content.col_date')); ?></th><th><?php echo e(__('content.col_time')); ?></th><th><?php echo e(__('content.col_note')); ?></th><th><?php echo e(__('content.col_status')); ?></th><th></th></tr></thead>
                    <tbody>
                        <?php $__currentLoopData = $selected->events->sortByDesc(fn ($e) => $e->schedules->first()?->date?->timestamp ?? PHP_INT_MAX); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="row-strong"><td colspan="4"><?php echo e($event->title); ?><?php if(! $event->is_published): ?> <span class="status-tag is-warn"><?php echo e(__('content.unpublished')); ?></span><?php endif; ?> <span class="status-tag <?php echo e($event->displayStatus() === 'cancelled' ? 'is-cancelled' : ($event->displayStatus() === 'ended' ? 'is-ended' : 'is-ok')); ?>"><?php echo e(__('content.display_'.$event->displayStatus())); ?></span></td>
                                <td class="actions">
                                    <a class="btn btn-sm" href="<?php echo e(route('admin.events.edit', $event)); ?>"><?php echo e(__('content.edit')); ?></a>
                                    <form method="post" action="<?php echo e(route('admin.events.copy', $event)); ?>" style="display:inline"><?php echo csrf_field(); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','size' => 'sm']); ?><?php echo e(__('content.copy_next_year')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></form>
                                </td>
                            </tr>
                            <?php $__currentLoopData = $event->schedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $schedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($schedule->date->isoFormat('YYYY/M/D(ddd)')); ?></td>
                                    <td><?php echo e($schedule->start_time ? substr($schedule->start_time, 0, 5) : ''); ?><?php if($schedule->end_time): ?>〜<?php echo e(substr($schedule->end_time, 0, 5)); ?><?php endif; ?></td>
                                    <td><?php echo e($schedule->note); ?></td>
                                    <td><span class="status-tag <?php echo e($schedule->is_cancelled ? 'is-cancelled' : ($schedule->date->toDateString() < $today ? 'is-ended' : 'is-ok')); ?>"><?php echo e($schedule->is_cancelled ? __('content.display_cancelled') : ($schedule->date->toDateString() < $today ? __('content.display_ended') : __('content.display_scheduled'))); ?></span></td>
                                    <td class="actions">
                                        <?php if($schedule->is_cancelled): ?>
                                            <form method="post" action="<?php echo e(route('admin.schedules.restore', $schedule)); ?>"><?php echo csrf_field(); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','size' => 'sm']); ?><?php echo e(__('content.restore_day')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></form>
                                        <?php elseif($schedule->date->toDateString() >= $today): ?>
                                            <form method="post" action="<?php echo e(route('admin.schedules.cancel', $schedule)); ?>"><?php echo csrf_field(); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','size' => 'sm']); ?><?php echo e(__('content.cancel_day')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <p class="t-caption t-muted"><?php echo e(__('content.cancel_note')); ?></p>
        </section>
    <?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $attributes = $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $component = $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/events/index.blade.php ENDPATH**/ ?>