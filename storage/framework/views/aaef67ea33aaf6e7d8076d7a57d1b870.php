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
    <h1 class="t-h1"><?php echo e($heading); ?></h1>
    <div class="list-layout">
        <aside class="filters">
            <form method="get" action="<?php echo e($basePath); ?>" data-filter-form data-api="<?php echo e(url('/api/v1/events')); ?>" data-pref="<?php echo e($pref); ?>">
                <div class="field">
                    <label for="f-q"><?php echo e(__('public.keyword')); ?></label>
                    <input id="f-q" name="q" type="search" maxlength="100" value="<?php echo e($query->q); ?>">
                </div>
                <div class="field">
                    <label for="f-when"><?php echo e(__('public.when')); ?></label>
                    <select id="f-when" name="when">
                        <option value=""><?php echo e(__('public.when_any')); ?></option>
                        <option value="today" <?php if($query->preset === 'today'): echo 'selected'; endif; ?>><?php echo e(__('public.today')); ?></option>
                        <option value="weekend" <?php if($query->preset === 'weekend'): echo 'selected'; endif; ?>><?php echo e(__('public.weekend')); ?></option>
                    </select>
                </div>
                <div class="field">
                    <label for="f-category"><?php echo e(__('public.category')); ?></label>
                    <select id="f-category" name="category">
                        <option value=""><?php echo e(__('public.category_any')); ?></option>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($c->slug); ?>" <?php if($query->categoryId === $c->id): echo 'selected'; endif; ?>><?php echo e($c->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="field">
                    <label for="f-from"><?php echo e(__('public.date_from')); ?></label>
                    <input id="f-from" name="from" type="date" value="<?php echo e($query->dateFrom); ?>">
                </div>
                <div class="field">
                    <label for="f-to"><?php echo e(__('public.date_to')); ?></label>
                    <input id="f-to" name="to" type="date" value="<?php echo e($query->dateTo); ?>">
                </div>
                <div class="field">
                    <label for="f-sort"><?php echo e(__('public.sort')); ?></label>
                    <select id="f-sort" name="sort">
                        <option value="date" <?php if($query->sort === 'date'): echo 'selected'; endif; ?>><?php echo e(__('public.sort_date')); ?></option>
                        <option value="popular" <?php if($query->sort === 'popular'): echo 'selected'; endif; ?>><?php echo e(__('public.sort_popular')); ?></option>
                    </select>
                </div>
                <label class="check-row"><input type="checkbox" name="past" value="1" <?php if($query->includePast): echo 'checked'; endif; ?>><span><?php echo e(__('public.include_past')); ?></span></label>
                <button class="btn btn-primary btn-block" type="submit"><?php echo e(__('public.apply')); ?></button>
                <a class="t-small" href="<?php echo e($basePath); ?>"><?php echo e(__('public.clear')); ?></a>
            </form>
        </aside>
        <div class="results">
            <?php echo $__env->make('public.events.partials.results', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $attributes = $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $component = $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/public/events/index.blade.php ENDPATH**/ ?>