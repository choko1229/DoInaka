<?php
    $scheduleRows = old('schedules') ?? $schedules->map(fn ($s) => [
        'date' => $s->date->toDateString(), 'start_time' => $s->start_time ? substr($s->start_time, 0, 5) : null,
        'end_time' => $s->end_time ? substr($s->end_time, 0, 5) : null, 'note' => $s->note, 'is_cancelled' => $s->is_cancelled,
    ])->all();
    $sourceRows = old('sources') ?? $sources->map(fn ($s) => [
        'kind' => $s->kind->value, 'url' => $s->url, 'title' => $s->title, 'checked_at' => $s->checked_at?->toDateString(), 'is_official' => $s->is_official,
    ])->all();
    if ($scheduleRows === []) { $scheduleRows = [['date' => null]]; }
    $state = old('state', $event->exists ? ($event->is_published ? 'published' : 'draft') : 'draft');
?>
<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('content.event_edit'),'current' => 'events']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('content.event_edit')),'current' => 'events']); ?>
    <div class="page-head">
        <h1 class="t-h1"><?php echo e($event->exists ? __('content.event_edit') : __('content.event_add')); ?></h1>
        <p class="t-small t-muted"><?php echo e(__('content.revision_note')); ?></p>
    </div>
    <p><a href="<?php echo e(route('admin.events', ['series' => $series->id])); ?>"><?php echo e(__('content.back_events')); ?></a> / <?php echo e($series->title); ?></p>

    <?php $__errorArgs = ['sources'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="alert alert-danger" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

    <form method="post" action="<?php echo e($event->exists ? route('admin.events.update', $event) : route('admin.events.store')); ?>" class="grid-main-side">
        <?php echo csrf_field(); ?>
        <?php if($event->exists): ?> <?php echo method_field('put'); ?> <?php else: ?> <input type="hidden" name="series_id" value="<?php echo e($series->id); ?>"> <?php endif; ?>

        <div>
            <section class="card">
                <h2 class="t-h2"><?php echo e(__('content.section_basic')); ?></h2>
                <div class="field">
                    <label for="title"><?php echo e(__('content.field_title')); ?></label>
                    <input id="title" name="title" value="<?php echo e(old('title', $event->title)); ?>" required maxlength="200">
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
                    <label for="category_id"><?php echo e(__('content.field_category')); ?></label>
                    <select id="category_id" name="category_id">
                        <option value="">—</option>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($category->id); ?>" <?php if((int) old('category_id', $event->category_id) === $category->id): echo 'selected'; endif; ?>><?php echo e($category->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="field">
                    <label for="tags"><?php echo e(__('content.field_tags')); ?></label>
                    <input id="tags" name="tags" value="<?php echo e(old('tags', $tagsText)); ?>" maxlength="300">
                    <p class="t-caption t-muted"><?php echo e(__('content.field_tags_help')); ?></p>
                </div>
            </section>

            <section class="card">
                <h2 class="t-h2"><?php echo e(__('content.section_place')); ?></h2>
                <div class="field">
                    <label for="region_id"><?php echo e(__('content.field_region')); ?></label>
                    <?php if (isset($component)) { $__componentOriginala078c6f22d93245976179f2b0be8d2dc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala078c6f22d93245976179f2b0be8d2dc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.region-select','data' => ['selected' => old('region_id', $event->region_id)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('region-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('region_id', $event->region_id))]); ?>
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
                    <label for="venue_name"><?php echo e(__('content.field_venue')); ?></label>
                    <input id="venue_name" name="venue_name" value="<?php echo e(old('venue_name', $event->venue_name)); ?>" maxlength="200">
                </div>
                <div class="field">
                    <label for="address"><?php echo e(__('content.field_address')); ?></label>
                    <input id="address" name="address" value="<?php echo e(old('address', $event->address)); ?>" maxlength="300">
                    <p class="t-caption t-muted"><?php echo e(__('content.field_address_help')); ?></p>
                </div>
                <div class="repeat-row">
                    <div class="field"><label for="lat"><?php echo e(__('content.field_lat')); ?></label><input id="lat" name="lat" inputmode="decimal" value="<?php echo e(old('lat', $event->lat)); ?>"></div>
                    <div class="field"><label for="lng"><?php echo e(__('content.field_lng')); ?></label><input id="lng" name="lng" inputmode="decimal" value="<?php echo e(old('lng', $event->lng)); ?>"></div>
                </div>
                <div class="field"><label for="fee"><?php echo e(__('content.field_fee')); ?></label><input id="fee" name="fee" value="<?php echo e(old('fee', $event->fee)); ?>" maxlength="200"></div>
                <div class="field"><label for="url"><?php echo e(__('content.field_url')); ?></label><input id="url" name="url" type="url" value="<?php echo e(old('url', $event->url)); ?>" maxlength="500"></div>
            </section>

            <section class="card">
                <h2 class="t-h2"><?php echo e(__('content.section_body')); ?></h2>
                <div class="field">
                    <label for="body"><?php echo e(__('content.field_body')); ?></label>
                    <textarea id="body" name="body" maxlength="20000"><?php echo e(old('body', $event->body)); ?></textarea>
                    <p class="t-caption t-muted"><?php echo e(__('content.field_body_help')); ?></p>
                </div>
            </section>

            <section class="card">
                <h2 class="t-h2"><?php echo e(__('content.section_sources')); ?> <span class="t-small" style="color:var(--danger)"><?php echo e(__('content.required_one')); ?></span></h2>
                <p class="t-small t-muted"><?php echo e(__('content.sources_help')); ?></p>
                <div data-repeat-target="sources" data-next-index="<?php echo e(count($sourceRows)); ?>">
                    <?php $__currentLoopData = array_values($sourceRows); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo $__env->make('admin.events.partials.source-row', ['i' => $i, 'row' => $row], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <template data-repeat-template="sources"><?php echo $__env->make('admin.events.partials.source-row', ['i' => '__INDEX__', 'row' => ['kind' => 'url']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></template>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'button','size' => 'sm','dataRepeatAdd' => 'sources']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','size' => 'sm','data-repeat-add' => 'sources']); ?><?php echo e(__('content.source_add')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            </section>

            <section class="card">
                <h2 class="t-h2"><?php echo e(__('content.section_schedules')); ?></h2>
                <div data-repeat-target="schedules" data-next-index="<?php echo e(count($scheduleRows)); ?>">
                    <?php $__currentLoopData = array_values($scheduleRows); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo $__env->make('admin.events.partials.schedule-row', ['i' => $i, 'row' => $row], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <template data-repeat-template="schedules"><?php echo $__env->make('admin.events.partials.schedule-row', ['i' => '__INDEX__', 'row' => []], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></template>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'button','size' => 'sm','dataRepeatAdd' => 'schedules']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','size' => 'sm','data-repeat-add' => 'schedules']); ?><?php echo e(__('content.schedule_add')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
                <p class="t-caption t-muted"><?php echo e(__('content.schedule_help')); ?></p>
            </section>

            <section class="card">
                <h2 class="t-h2"><?php echo e(__('content.section_url')); ?></h2>
                <div class="field">
                    <label for="slug"><?php echo e(__('content.field_slug')); ?></label>
                    <input id="slug" name="slug" value="<?php echo e(old('slug', $event->slug)); ?>" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                    <p class="t-caption t-muted"><?php echo e(__('content.field_slug_help')); ?></p>
                </div>
            </section>
        </div>

        <aside class="card">
            <h2 class="t-h2"><?php echo e(__('content.section_publish')); ?></h2>
            <fieldset class="field">
                <legend><?php echo e(__('content.field_state')); ?></legend>
                <label><input type="radio" name="state" value="published" <?php if($state === 'published'): echo 'checked'; endif; ?>> <?php echo e(__('content.state_published')); ?></label>
                <label><input type="radio" name="state" value="draft" <?php if($state !== 'published'): echo 'checked'; endif; ?>> <?php echo e(__('content.state_draft')); ?></label>
            </fieldset>
            <?php if($event->exists): ?>
                <label class="check-row"><input type="checkbox" name="is_postponed" value="1" <?php if(old('is_postponed', $event->is_postponed)): echo 'checked'; endif; ?>><span><?php echo e(__('content.field_postponed')); ?></span></label>
            <?php endif; ?>
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
            <?php if($event->exists): ?>
                <p class="t-caption t-muted"><?php echo e(__('content.last_updated', ['at' => $event->updated_at?->setTimezone('Asia/Tokyo')->format('n/j G:i')])); ?></p>
                <p><a href="<?php echo e(route('admin.revisions', ['type' => 'event', 'id' => $event->id])); ?>"><?php echo e(__('content.history_count', ['count' => $revisionCount])); ?></a></p>
            <?php endif; ?>
        </aside>
    </form>

    <?php if($event->exists): ?>
        <section class="card">
            <h2 class="t-h3"><?php echo e(__('content.danger_zone')); ?></h2>
            <div class="button-row">
                <form method="post" action="<?php echo e(route('admin.events.cancel', $event)); ?>"><?php echo csrf_field(); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?><?php echo e(__('content.cancel_event')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></form>
                <form method="post" action="<?php echo e(route('admin.events.copy', $event)); ?>"><?php echo csrf_field(); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?><?php echo e(__('content.copy_next_year')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></form>
                <form method="post" action="<?php echo e(route('admin.events.destroy', $event)); ?>" onsubmit="return confirm(<?php echo \Illuminate\Support\Js::from(__('content.delete_confirm'))->toHtml() ?>)"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?><?php echo e(__('content.delete_mistake')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></form>
            </div>
            <p class="t-caption t-muted"><?php echo e(__('content.delete_help')); ?></p>
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/events/event-form.blade.php ENDPATH**/ ?>