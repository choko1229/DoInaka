<?php if (isset($component)) { $__componentOriginalc7b6b0f5d16f8a605a52718a155b5029 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.install','data' => ['step' => 1]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.install'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['step' => 1]); ?>
    <p><?php echo e(__('install.lead')); ?></p>

    <section class="card">
        <h2 class="t-h2"><?php echo e(__('install.environment')); ?></h2>
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
        <?php if (! ($canContinue)): ?>
            <p class="alert alert-danger" role="alert"><?php echo e(__('install.environment_failed')); ?></p>
        <?php endif; ?>
    </section>

    <?php if($canContinue): ?>
        <section class="card">
            <h2 class="t-h2"><?php echo e(__('install.key_title')); ?></h2>
            <p class="t-small t-muted"><?php echo e(__('install.key_help', ['path' => $keyPath])); ?></p>
            <form method="post" action="<?php echo e(route('install.verify')); ?>">
                <?php echo csrf_field(); ?>
                <div class="field">
                    <label for="install_key"><?php echo e(__('install.key_label')); ?></label>
                    <input id="install_key" name="install_key" type="text" autocomplete="off" required spellcheck="false">
                    <?php $__errorArgs = ['install_key'];
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
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary']); ?><?php echo e(__('install.next')); ?> <?php echo $__env->renderComponent(); ?>
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
    <?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029)): ?>
<?php $attributes = $__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029; ?>
<?php unset($__attributesOriginalc7b6b0f5d16f8a605a52718a155b5029); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc7b6b0f5d16f8a605a52718a155b5029)): ?>
<?php $component = $__componentOriginalc7b6b0f5d16f8a605a52718a155b5029; ?>
<?php unset($__componentOriginalc7b6b0f5d16f8a605a52718a155b5029); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/install/index.blade.php ENDPATH**/ ?>