<?php if (isset($component)) { $__componentOriginalc7b6b0f5d16f8a605a52718a155b5029 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.install','data' => ['step' => 3]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.install'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['step' => 3]); ?>
    <form method="post" action="<?php echo e(route('install.site.save')); ?>" class="card">
        <?php echo csrf_field(); ?>
        <h2 class="t-h2"><?php echo e(__('install.site_title')); ?></h2>

        <div class="field">
            <label for="site_name"><?php echo e(__('install.site_name')); ?></label>
            <input id="site_name" name="site_name" type="text" value="<?php echo e(old('site_name', 'ド田舎.net')); ?>" required>
            <?php $__errorArgs = ['site_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="field">
            <label for="site_description"><?php echo e(__('install.site_description')); ?></label>
            <input id="site_description" name="site_description" type="text" value="<?php echo e(old('site_description')); ?>">
            <?php $__errorArgs = ['site_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="field">
            <label for="admin_name"><?php echo e(__('install.admin_name')); ?></label>
            <input id="admin_name" name="admin_name" type="text" value="<?php echo e(old('admin_name')); ?>" required>
            <?php $__errorArgs = ['admin_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="field">
            <label for="admin_email"><?php echo e(__('install.admin_email')); ?></label>
            <input id="admin_email" name="admin_email" type="email" value="<?php echo e(old('admin_email')); ?>" required autocomplete="off">
            <p class="t-small t-muted"><?php echo e(__('install.admin_email_help')); ?></p>
            <?php $__errorArgs = ['admin_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <h3 class="t-h3"><?php echo e(__('install.google_title')); ?></h3>
        <div class="field">
            <label for="google_client_id"><?php echo e(__('install.google_client_id')); ?></label>
            <input id="google_client_id" name="google_client_id" type="text" value="<?php echo e(old('google_client_id')); ?>" autocomplete="off">
            <?php $__errorArgs = ['google_client_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="field">
            <label for="google_client_secret"><?php echo e(__('install.google_client_secret')); ?></label>
            <input id="google_client_secret" name="google_client_secret" type="password" autocomplete="off">
            <?php $__errorArgs = ['google_client_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary']); ?><?php echo e(__('install.finish')); ?> <?php echo $__env->renderComponent(); ?>
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
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029)): ?>
<?php $attributes = $__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029; ?>
<?php unset($__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc7b6b0f5d16f8a605a52718a155b5029)): ?>
<?php $component = $__componentOriginalc7b6b0f5d16f8a605a52718a155b5029; ?>
<?php unset($__componentOriginalc7b6b0f5d16f8a605a52718a155b5029); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/install/site.blade.php ENDPATH**/ ?>