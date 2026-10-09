<?php if (isset($component)) { $__componentOriginala4dba0b979c83f75f6062075e1003056 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala4dba0b979c83f75f6062075e1003056 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin-auth','data' => ['step' => 3]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin-auth'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['step' => 3]); ?>
    <h1 class="t-h1"><?php echo e(__('auth.setup_title')); ?></h1>
    <p><?php echo e(__('auth.setup_lead')); ?></p>
    <ol>
        <li><?php echo e(__('auth.setup_step_1')); ?></li>
        <li><?php echo e(__('auth.setup_step_2')); ?></li>
        <li><?php echo e(__('auth.setup_step_3')); ?></li>
    </ol>
    <div class="qr-row">
        <div class="qr" role="img" aria-label="QR"><?php echo $qr; ?></div>
        <div>
            <p class="t-caption t-muted"><?php echo e(__('auth.setup_key_help')); ?></p>
            <p class="key" id="totp-key"><?php echo e($key); ?></p>
            <button type="button" class="btn btn-sm" data-copy="#totp-key" data-copied="<?php echo e(__('auth.copied')); ?>"><?php echo e(__('auth.setup_copy')); ?></button>
        </div>
    </div>
    <form method="post" action="<?php echo e(route('admin.two-factor.enable')); ?>">
        <?php echo csrf_field(); ?>
        <div class="field">
            <label for="code"><?php echo e(__('auth.code_label')); ?></label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="20" required class="code-input" placeholder="000000">
            <?php $__errorArgs = ['code'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'primary','class' => 'btn-block']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary','class' => 'btn-block']); ?><?php echo e(__('auth.setup_submit')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php if (isset($__attributesOriginala4dba0b979c83f75f6062075e1003056)): ?>
<?php $attributes = $__attributesOriginala4dba0b979c83f75f6062075e1003056; ?>
<?php unset($__attributesOriginala4dba0b979c83f75f6062075e1003056); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala4dba0b979c83f75f6062075e1003056)): ?>
<?php $component = $__componentOriginala4dba0b979c83f75f6062075e1003056; ?>
<?php unset($__componentOriginala4dba0b979c83f75f6062075e1003056); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/auth/setup.blade.php ENDPATH**/ ?>