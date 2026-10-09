<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('content.series_edit'),'current' => 'events']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('content.series_edit')),'current' => 'events']); ?>
    <div class="page-head">
        <h1 class="t-h1"><?php echo e($series->exists ? __('content.series_edit') : __('content.series_add')); ?></h1>
        <p class="t-small t-muted"><?php echo e(__('content.revision_note')); ?></p>
    </div>
    <p><a href="<?php echo e(route('admin.events')); ?>"><?php echo e(__('content.back_events')); ?></a></p>

    <form method="post" action="<?php echo e($series->exists ? route('admin.series.update', $series) : route('admin.series.store')); ?>" class="grid-main-side">
        <?php echo csrf_field(); ?>
        <?php if($series->exists): ?> <?php echo method_field('put'); ?> <?php endif; ?>
        <section class="card">
            <div class="field">
                <label for="title"><?php echo e(__('content.field_series_title')); ?></label>
                <input id="title" name="title" value="<?php echo e(old('title', $series->title)); ?>" required maxlength="200">
                <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="field">
                <label for="region_id"><?php echo e(__('content.field_region')); ?></label>
                <?php if (isset($component)) { $__componentOriginala078c6f22d93245976179f2b0be8d2dc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala078c6f22d93245976179f2b0be8d2dc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.region-select','data' => ['selected' => old('region_id', $series->region_id)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('region-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('region_id', $series->region_id))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala078c6f22d93245976179f2b0be8d2dc)): ?>
<?php $attributes = $__attributesOriginala078c6f22d93245976179f2b0be8d2dc; ?>
<?php unset($__attributesOriginala078c6f22d93245976179f2b0be8d2dc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala078c6f22d93245976179f2b0be8d2dc)): ?>
<?php $component = $__componentOriginala078c6f22d93245976179f2b0be8d2dc; ?>
<?php unset($__componentOriginala078c6f22d93245976179f2b0be8d2dc); ?>
<?php endif; ?>
                <?php $__errorArgs = ['region_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="field">
                <label for="category_id"><?php echo e(__('content.field_category')); ?></label>
                <select id="category_id" name="category_id">
                    <option value="">—</option>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($category->id); ?>" <?php if((int) old('category_id', $series->category_id) === $category->id): echo 'selected'; endif; ?>><?php echo e($category->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <fieldset class="field">
                <legend><?php echo e(__('content.field_recurrence')); ?></legend>
                <?php $__currentLoopData = \App\Enums\Recurrence::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label><input type="radio" name="recurrence" value="<?php echo e($r->value); ?>" <?php if(old('recurrence', $series->recurrence->value) === $r->value): echo 'checked'; endif; ?>> <?php echo e($r->label()); ?></label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </fieldset>
            <div class="field">
                <label for="summary"><?php echo e(__('content.field_summary')); ?></label>
                <textarea id="summary" name="summary" maxlength="2000"><?php echo e(old('summary', $series->summary)); ?></textarea>
            </div>
            <div class="field">
                <label for="slug"><?php echo e(__('content.field_slug')); ?></label>
                <input id="slug" name="slug" value="<?php echo e(old('slug', $series->slug)); ?>" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*">
            </div>
        </section>
        <aside class="card">
            <div class="field">
                <label for="reason"><?php echo e(__('content.field_reason')); ?></label>
                <input id="reason" name="reason" maxlength="200" value="<?php echo e(old('reason')); ?>">
                <p class="t-caption t-muted"><?php echo e(__('content.field_reason_help')); ?></p>
            </div>
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary']); ?><?php echo e(__('content.save')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            <?php if($series->exists): ?>
                <p><a href="<?php echo e(route('admin.revisions', ['type' => 'series', 'id' => $series->id])); ?>"><?php echo e(__('content.history')); ?></a></p>
                <p><a class="btn btn-sm" href="<?php echo e(route('admin.events.create', ['series' => $series->id])); ?>"><?php echo e(__('content.event_add')); ?></a></p>
                <h2 class="t-h3"><?php echo e(__('content.events_of', ['title' => $series->title])); ?></h2>
                <ul class="t-small">
                    <?php $__currentLoopData = $series->events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><a href="<?php echo e(route('admin.events.edit', $e)); ?>"><?php echo e($e->schedules->first()?->date?->format('Y/m/d') ?? __('content.none')); ?></a> <?php echo e(__('content.display_'.$e->displayStatus())); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php endif; ?>
        </aside>
    </form>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $attributes = $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $component = $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/events/series-form.blade.php ENDPATH**/ ?>