<?php
    $meta = new \App\Support\PageMeta(title: __('submission.form_title', ['type' => __('enums.submission_type.'.$type->value)]), noindex: true);
    $field = fn (string $name) => $errors->first($name);
?>
<?php if (isset($component)) { $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.public','data' => ['meta' => $meta,'current' => 'post']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.public'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'current' => 'post']); ?>
    <h1 class="t-display"><?php echo e(__('enums.submission_type.'.$type->value)); ?></h1>
    <p><?php echo e(__('submission.lead_'.$type->value)); ?></p>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger" role="alert">
            <p class="t-strong" style="margin:0"><?php echo e(__('submission.errors_title')); ?></p>
            <ul class="t-small"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($message); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
        </div>
    <?php endif; ?>

    <form class="card container-narrow" method="post" action="/post/<?php echo e($type->value); ?>/" enctype="multipart/form-data" novalidate>
        <?php echo csrf_field(); ?>

        <?php if($type === \App\Enums\SubmissionType::Tip): ?>
            <div class="field">
                <label for="source_url"><?php echo e(__('submission.tip_url')); ?></label>
                <input id="source_url" type="url" name="source_url" maxlength="500" value="<?php echo e(old('source_url')); ?>" placeholder="https://" aria-describedby="source-hint">
                <p id="source-hint" class="t-small t-muted"><?php echo e(__('submission.tip_url_hint')); ?></p>
                <?php $__errorArgs = ['source_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <?php echo $__env->make('public.post.partials.photos', ['label' => __('submission.tip_photos')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('public.post.partials.region', ['required' => false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="field">
                <label for="note"><?php echo e(__('submission.tip_note')); ?></label>
                <textarea id="note" name="note" maxlength="500" rows="3"><?php echo e(old('note')); ?></textarea>
                <p class="t-small t-muted"><?php echo e(__('submission.tip_note_hint')); ?></p>
                <?php $__errorArgs = ['note'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        <?php else: ?>
            <?php echo $__env->make('public.post.partials.region', ['required' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="field">
                <label for="title"><?php echo e(__('submission.title')); ?></label>
                <input id="title" type="text" name="title" maxlength="200" value="<?php echo e(old('title')); ?>" required>
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
                <label for="body"><?php echo e(__('submission.body')); ?></label>
                <textarea id="body" name="body" maxlength="<?php echo e($type === \App\Enums\SubmissionType::Article ? 20000 : 5000); ?>" rows="<?php echo e($type === \App\Enums\SubmissionType::Article ? 12 : 6); ?>" required><?php echo e(old('body')); ?></textarea>
                <p class="t-small t-muted"><?php echo e(__('submission.body_hint')); ?></p>
                <?php $__errorArgs = ['body'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <?php if($type === \App\Enums\SubmissionType::Spot): ?>
                <div class="field">
                    <label for="category_id"><?php echo e(__('submission.category')); ?></label>
                    <select id="category_id" name="category_id">
                        <option value="">—</option>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($category->id); ?>" <?php if((int) old('category_id') === $category->id): echo 'selected'; endif; ?>><?php echo e($category->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="field"><label for="address"><?php echo e(__('submission.address')); ?></label><input id="address" type="text" name="address" maxlength="300" value="<?php echo e(old('address')); ?>"></div>
                <div class="field">
                    <p class="t-strong" style="margin:0"><?php echo e(__('submission.location')); ?></p>
                    <p class="t-small t-muted"><?php echo e(__('submission.location_hint')); ?></p>
                    <div class="map" data-map-picker role="application" aria-label="<?php echo e(__('submission.location')); ?>"></div>
                    <input type="hidden" name="lat" value="<?php echo e(old('lat')); ?>" data-lat>
                    <input type="hidden" name="lng" value="<?php echo e(old('lng')); ?>" data-lng>
                    <?php $__errorArgs = ['lat'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="field"><label for="hours"><?php echo e(__('submission.hours')); ?></label><input id="hours" type="text" name="hours" maxlength="300" value="<?php echo e(old('hours')); ?>"></div>
                <div class="field"><label for="access"><?php echo e(__('submission.access')); ?></label><input id="access" type="text" name="access" maxlength="500" value="<?php echo e(old('access')); ?>"></div>
                <div class="field"><label for="url"><?php echo e(__('submission.url')); ?></label><input id="url" type="url" name="url" maxlength="500" value="<?php echo e(old('url')); ?>" placeholder="https://"><?php $__errorArgs = ['url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            <?php endif; ?>
            <div class="field"><label for="tags"><?php echo e(__('submission.tags')); ?></label><input id="tags" type="text" name="tags" maxlength="200" value="<?php echo e(old('tags')); ?>"><p class="t-small t-muted"><?php echo e(__('submission.tags_hint')); ?></p></div>
            <?php echo $__env->make('public.post.partials.photos', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        <?php if (isset($component)) { $__componentOriginald32f4393a63128a5c229b85fe00e906a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald32f4393a63128a5c229b85fe00e906a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.turnstile','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('turnstile'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald32f4393a63128a5c229b85fe00e906a)): ?>
<?php $attributes = $__attributesOriginald32f4393a63128a5c229b85fe00e906a; ?>
<?php unset($__attributesOriginald32f4393a63128a5c229b85fe00e906a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald32f4393a63128a5c229b85fe00e906a)): ?>
<?php $component = $__componentOriginald32f4393a63128a5c229b85fe00e906a; ?>
<?php unset($__componentOriginald32f4393a63128a5c229b85fe00e906a); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal2dc3a0c4f59804dcb82c363f29f22111 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2dc3a0c4f59804dcb82c363f29f22111 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.consent','data' => ['overseas' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('consent'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['overseas' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2dc3a0c4f59804dcb82c363f29f22111)): ?>
<?php $attributes = $__attributesOriginal2dc3a0c4f59804dcb82c363f29f22111; ?>
<?php unset($__attributesOriginal2dc3a0c4f59804dcb82c363f29f22111); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2dc3a0c4f59804dcb82c363f29f22111)): ?>
<?php $component = $__componentOriginal2dc3a0c4f59804dcb82c363f29f22111; ?>
<?php unset($__componentOriginal2dc3a0c4f59804dcb82c363f29f22111); ?>
<?php endif; ?>
        <button class="btn btn-primary" type="submit"><?php echo e(__('submission.send')); ?></button>
    </form>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $attributes = $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $component = $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/public/post/form.blade.php ENDPATH**/ ?>