<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('masters.region_edit'),'current' => 'masters']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('masters.region_edit')),'current' => 'masters']); ?>
    <div class="page-head">
        <h1 class="t-h1"><?php echo e(__('masters.region_edit')); ?></h1>
        <p class="t-small t-muted"><?php echo e(__('masters.lead')); ?></p>
    </div>
    <p><a href="<?php echo e(route('admin.masters', ['tab' => 'regions'])); ?>"><?php echo e(__('masters.back')); ?></a></p>

    <form method="post" action="<?php echo e(route('admin.masters.regions.update', $region)); ?>" class="grid-main-side">
        <?php echo csrf_field(); ?>
        <?php echo method_field('put'); ?>
        <section class="card">
            <h2 class="t-h2"><?php echo e($region->level->label()); ?><?php if($region->era): ?> <span class="pill"><?php echo e($region->era->label()); ?></span><?php endif; ?></h2>
            <div class="field">
                <label for="name"><?php echo e(__('masters.field_name')); ?></label>
                <input id="name" name="name" value="<?php echo e(old('name', $region->name)); ?>" required maxlength="100">
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="field">
                <label for="name_kana"><?php echo e(__('masters.field_kana')); ?></label>
                <input id="name_kana" name="name_kana" value="<?php echo e(old('name_kana', $region->name_kana)); ?>" maxlength="150">
            </div>
            <div class="field">
                <label for="slug"><?php echo e(__('masters.field_slug')); ?></label>
                <input id="slug" name="slug" value="<?php echo e(old('slug', $region->slug)); ?>" required maxlength="100" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                <p class="t-small t-muted"><?php echo e(__('masters.field_slug_help')); ?></p>
                <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <p class="t-small t-muted"><?php echo e(__('masters.url_preview')); ?>: <?php echo e(url('/'.$region->path().'/')); ?></p>
            </div>

            <?php if($region->level === \App\Enums\RegionLevel::OldMunicipality): ?>
                <div class="field">
                    <label for="parent_id"><?php echo e(__('masters.field_parent')); ?></label>
                    <select id="parent_id" name="parent_id">
                        <?php $__currentLoopData = $parents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $parent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($parent->id); ?>" <?php if((int) old('parent_id', $region->parent_id) === $parent->id): echo 'selected'; endif; ?>><?php echo e($parent->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="field">
                    <label for="former_parent_id"><?php echo e(__('masters.field_former_parent')); ?></label>
                    <select id="former_parent_id" name="former_parent_id">
                        <option value="">—</option>
                        <?php $__currentLoopData = $formerParents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $former): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($former->id); ?>" <?php if((int) old('former_parent_id', $region->former_parent_id) === $former->id): echo 'selected'; endif; ?>><?php echo e($former->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <fieldset class="field">
                    <legend><?php echo e(__('masters.field_era')); ?></legend>
                    <?php $__currentLoopData = \App\Enums\EraTag::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $era): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label><input type="radio" name="era" value="<?php echo e($era->value); ?>" <?php if(old('era', $region->era?->value) === $era->value): echo 'checked'; endif; ?>> <?php echo e($era->label()); ?></label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </fieldset>
                <dl class="facts">
                    <dt><?php echo e(__('masters.abolished')); ?></dt><dd><?php echo e($region->abolished_on?->format('Y/m/d')); ?></dd>
                    <dt><?php echo e(__('masters.merged_into')); ?></dt><dd><?php echo e($region->merged_into); ?></dd>
                </dl>
                <p class="t-caption t-muted"><?php echo e(__('masters.region_info')); ?></p>
            <?php endif; ?>
        </section>

        <aside class="card">
            <h2 class="t-h2"><?php echo e(__('masters.field_active')); ?></h2>
            <label class="check-row"><input type="checkbox" name="is_active" value="1" <?php if(old('is_active', $region->is_active)): echo 'checked'; endif; ?>><span><?php echo e(__('masters.field_active')); ?></span></label>
            <div class="field">
                <label for="reason"><?php echo e(__('masters.field_reason')); ?></label>
                <input id="reason" name="reason" maxlength="200" value="<?php echo e(old('reason')); ?>">
                <p class="t-caption t-muted"><?php echo e(__('masters.field_reason_help')); ?></p>
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
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary']); ?><?php echo e(__('masters.save')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            <p><a href="<?php echo e(route('admin.revisions', ['type' => 'region', 'id' => $region->id])); ?>"><?php echo e(__('masters.history', ['count' => $revisionCount])); ?></a></p>
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/masters/region-edit.blade.php ENDPATH**/ ?>