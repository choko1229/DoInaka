<?php if (isset($component)) { $__componentOriginala4dba0b979c83f75f6062075e1003056 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala4dba0b979c83f75f6062075e1003056 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin-auth','data' => ['step' => 2]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin-auth'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['step' => 2]); ?>
    <h1 class="t-h1"><?php echo e(__('auth.two_factor_title')); ?></h1>
    <p><?php echo e(__('auth.two_factor_lead')); ?></p>
    <form method="post" action="<?php echo e(route('admin.two-factor.verify')); ?>">
        <?php echo csrf_field(); ?>
        <div class="field">
            <label for="code"><?php echo e(__('auth.code_label')); ?></label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="20" required autofocus class="code-input">
            <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <label class="check-row">
            <input type="checkbox" name="remember" value="1">
            <span><?php echo e(__('auth.remember', ['days' => $days])); ?></span>
        </label>
        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'primary','class' => 'btn-block']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary','class' => 'btn-block']); ?><?php echo e(__('auth.confirm')); ?> <?php echo $__env->renderComponent(); ?>
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
    <p><a href="<?php echo e(route('admin.two-factor.recovery')); ?>"><?php echo e(__('auth.use_recovery')); ?></a></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala4dba0b979c83f75f6062075e1003056)): ?>
<?php $attributes = $__attributesOriginala4dba0b979c83f75f6062075e1003056; ?>
<?php unset($__attributesOriginala4dba0b979c83f75f6062075e1003056); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala4dba0b979c83f75f6062075e1003056)): ?>
<?php $component = $__componentOriginala4dba0b979c83f75f6062075e1003056; ?>
<?php unset($__componentOriginala4dba0b979c83f75f6062075e1003056); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/auth/challenge.blade.php ENDPATH**/ ?>