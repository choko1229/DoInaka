<?php ($state = old('state', $spot->exists ? ($spot->is_published ? 'published' : 'draft') : 'draft')); ?>
<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('content.spot_edit'),'current' => 'contents']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('content.spot_edit')),'current' => 'contents']); ?>
    <div class="page-head">
        <h1 class="t-h1"><?php echo e($spot->exists ? __('content.spot_edit') : __('content.spot_add')); ?></h1>
        <p class="t-small t-muted"><?php echo e(__('content.revision_note')); ?></p>
    </div>
    <p><a href="<?php echo e(route('admin.contents', ['tab' => 'spot'])); ?>"><?php echo e(__('content.back_contents')); ?></a></p>

    <form method="post" action="<?php echo e($spot->exists ? route('admin.spots.update', $spot) : route('admin.spots.store')); ?>" class="grid-main-side">
        <?php echo csrf_field(); ?>
        <?php if($spot->exists): ?> <?php echo method_field('put'); ?> <?php endif; ?>
        <section class="card">
            <div class="field"><label for="title"><?php echo e(__('content.field_title')); ?></label><input id="title" name="title" value="<?php echo e(old('title', $spot->title)); ?>" required maxlength="200"><?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            <div class="field"><label for="category_id"><?php echo e(__('content.field_category')); ?></label>
                <select id="category_id" name="category_id"><option value="">—</option><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($category->id); ?>" <?php if((int) old('category_id', $spot->category_id) === $category->id): echo 'selected'; endif; ?>><?php echo e($category->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
            <div class="field"><label for="region_id"><?php echo e(__('content.field_region')); ?></label><?php if (isset($component)) { $__componentOriginala078c6f22d93245976179f2b0be8d2dc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala078c6f22d93245976179f2b0be8d2dc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.region-select','data' => ['selected' => old('region_id', $spot->region_id)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('region-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('region_id', $spot->region_id))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala078c6f22d93245976179f2b0be8d2dc)): ?>
<?php $attributes = $__attributesOriginala078c6f22d93245976179f2b0be8d2dc; ?>
<?php unset($__attributesOriginala078c6f22d93245976179f2b0be8d2dc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala078c6f22d93245976179f2b0be8d2dc)): ?>
<?php $component = $__componentOriginala078c6f22d93245976179f2b0be8d2dc; ?>
<?php unset($__componentOriginala078c6f22d93245976179f2b0be8d2dc); ?>
<?php endif; ?><?php $__errorArgs = ['region_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            <div class="field"><label for="address"><?php echo e(__('content.field_address')); ?></label><input id="address" name="address" value="<?php echo e(old('address', $spot->address)); ?>" maxlength="300"></div>
            <div class="repeat-row">
                <div class="field"><label for="lat"><?php echo e(__('content.field_lat')); ?></label><input id="lat" name="lat" inputmode="decimal" value="<?php echo e(old('lat', $spot->lat)); ?>"></div>
                <div class="field"><label for="lng"><?php echo e(__('content.field_lng')); ?></label><input id="lng" name="lng" inputmode="decimal" value="<?php echo e(old('lng', $spot->lng)); ?>"></div>
            </div>
            <div class="field"><label for="hours"><?php echo e(__('content.field_hours')); ?></label><input id="hours" name="hours" value="<?php echo e(old('hours', $spot->hours)); ?>" maxlength="300"></div>
            <div class="field"><label for="access"><?php echo e(__('content.field_access')); ?></label><input id="access" name="access" value="<?php echo e(old('access', $spot->access)); ?>" maxlength="300"></div>
            <div class="field"><label for="url"><?php echo e(__('content.field_url')); ?></label><input id="url" name="url" type="url" value="<?php echo e(old('url', $spot->url)); ?>" maxlength="500"></div>
            <div class="field"><label for="body"><?php echo e(__('content.field_body')); ?></label><textarea id="body" name="body" maxlength="20000"><?php echo e(old('body', $spot->body)); ?></textarea></div>
            <div class="field"><label for="tags"><?php echo e(__('content.field_tags')); ?></label><input id="tags" name="tags" value="<?php echo e(old('tags', $tagsText)); ?>" maxlength="300"><p class="t-caption t-muted"><?php echo e(__('content.field_tags_help')); ?></p></div>
            <div class="field"><label for="slug"><?php echo e(__('content.field_slug')); ?></label><input id="slug" name="slug" value="<?php echo e(old('slug', $spot->slug)); ?>" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*"></div>
        </section>
        <aside class="card">
            <fieldset class="field"><legend><?php echo e(__('content.field_state')); ?></legend>
                <label><input type="radio" name="state" value="published" <?php if($state === 'published'): echo 'checked'; endif; ?>> <?php echo e(__('content.state_published')); ?></label>
                <label><input type="radio" name="state" value="draft" <?php if($state !== 'published'): echo 'checked'; endif; ?>> <?php echo e(__('content.state_draft')); ?></label></fieldset>
            <div class="field"><label for="reason"><?php echo e(__('content.field_reason')); ?></label><input id="reason" name="reason" maxlength="200" value="<?php echo e(old('reason')); ?>"><p class="t-caption t-muted"><?php echo e(__('content.field_reason_help')); ?></p></div>
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
            <?php if($spot->exists): ?>
                <p><a href="<?php echo e(route('admin.revisions', ['type' => 'spot', 'id' => $spot->id])); ?>"><?php echo e(__('content.history_count', ['count' => $revisionCount])); ?></a></p>
            <?php endif; ?>
        </aside>
    </form>
    <?php if($spot->exists): ?>
        <form method="post" action="<?php echo e(route('admin.spots.destroy', $spot)); ?>" onsubmit="return confirm(<?php echo \Illuminate\Support\Js::from(__('content.delete_confirm'))->toHtml() ?>)"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/contents/spot-form.blade.php ENDPATH**/ ?>