<?php if (isset($component)) { $__componentOriginalc7b6b0f5d16f8a605a52718a155b5029 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.install','data' => ['step' => 2]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.install'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['step' => 2]); ?>
    <section class="card">
        <h2 class="t-h2"><?php echo e(__('install.db_title')); ?></h2>
        <p class="t-small t-muted"><?php echo e(__('install.db_help')); ?></p>

        <?php if($results !== []): ?>
            <?php if (isset($component)) { $__componentOriginalc4f51a3abab944f6a3bc13881a2ac6bf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4f51a3abab944f6a3bc13881a2ac6bf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.check-list','data' => ['results' => $results]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('check-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['results' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($results)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4f51a3abab944f6a3bc13881a2ac6bf)): ?>
<?php $attributes = $__attributesOriginalc4f51a3abab944f6a3bc13881a2ac6bf; ?>
<?php unset($__attributesOriginalc4f51a3abab944f6a3bc13881a2ac6bf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4f51a3abab944f6a3bc13881a2ac6bf)): ?>
<?php $component = $__componentOriginalc4f51a3abab944f6a3bc13881a2ac6bf; ?>
<?php unset($__componentOriginalc4f51a3abab944f6a3bc13881a2ac6bf); ?>
<?php endif; ?>
        <?php endif; ?>

        <form method="post" action="<?php echo e(route('install.database.save')); ?>">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="host"><?php echo e(__('install.db_host')); ?></label>
                <input id="host" name="host" type="text" value="<?php echo e(old('host', $values['host'])); ?>" required autocomplete="off">
                <?php $__errorArgs = ['host'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="field">
                <label for="port"><?php echo e(__('install.db_port')); ?></label>
                <input id="port" name="port" type="number" min="1" max="65535" value="<?php echo e(old('port', $values['port'])); ?>" required>
                <?php $__errorArgs = ['port'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="field">
                <label for="database"><?php echo e(__('install.db_database')); ?></label>
                <input id="database" name="database" type="text" value="<?php echo e(old('database', $values['database'])); ?>" required autocomplete="off">
                <?php $__errorArgs = ['database'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="field">
                <label for="username"><?php echo e(__('install.db_username')); ?></label>
                <input id="username" name="username" type="text" value="<?php echo e(old('username', $values['username'])); ?>" required autocomplete="off">
                <?php $__errorArgs = ['username'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="field">
                <label for="password"><?php echo e(__('install.db_password')); ?></label>
                <input id="password" name="password" type="password" autocomplete="off">
                <?php $__errorArgs = ['password'];
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
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary']); ?><?php echo e(__('install.db_check')); ?> <?php echo $__env->renderComponent(); ?>
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
    </section>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029)): ?>
<?php $attributes = $__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029; ?>
<?php unset($__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc7b6b0f5d16f8a605a52718a155b5029)): ?>
<?php $component = $__componentOriginalc7b6b0f5d16f8a605a52718a155b5029; ?>
<?php unset($__componentOriginalc7b6b0f5d16f8a605a52718a155b5029); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/install/database.blade.php ENDPATH**/ ?>