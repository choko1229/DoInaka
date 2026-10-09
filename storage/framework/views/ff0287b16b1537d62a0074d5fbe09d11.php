<?php if (isset($component)) { $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.public','data' => ['title' => __('auth.login_title'),'noindex' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.public'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('auth.login_title')),'noindex' => true]); ?>
    <div class="login-layout">
        <section class="card login-card">
            <h1 class="t-h1"><?php echo e(__('auth.login_title')); ?></h1>
            <p><?php echo e(__('auth.login_lead')); ?></p>

            <?php $__errorArgs = ['google'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="alert alert-danger" role="alert"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

            <?php if (isset($component)) { $__componentOriginal5c54a6d4d676c1c26a467c6a9c275c37 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c54a6d4d676c1c26a467c6a9c275c37 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.google-button','data' => ['href' => route('auth.google'),'class' => 'google-btn-block']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('google-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('auth.google')),'class' => 'google-btn-block']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c54a6d4d676c1c26a467c6a9c275c37)): ?>
<?php $attributes = $__attributesOriginal5c54a6d4d676c1c26a467c6a9c275c37; ?>
<?php unset($__attributesOriginal5c54a6d4d676c1c26a467c6a9c275c37); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c54a6d4d676c1c26a467c6a9c275c37)): ?>
<?php $component = $__componentOriginal5c54a6d4d676c1c26a467c6a9c275c37; ?>
<?php unset($__componentOriginal5c54a6d4d676c1c26a467c6a9c275c37); ?>
<?php endif; ?>

            <div class="notes">
                <p class="t-strong" style="margin:0"><?php echo e(__('auth.login_benefits_title')); ?></p>
                <ul class="t-small">
                    <li><?php echo e(__('auth.login_benefit_1')); ?></li>
                    <li><?php echo e(__('auth.login_benefit_2')); ?></li>
                    <li><?php echo e(__('auth.login_benefit_3')); ?></li>
                </ul>
            </div>

            <p class="t-small t-muted"><?php echo __('auth.login_consent', [
                'terms' => '<a href="'.e(url('/terms/')).'">'.e(__('auth.terms')).'</a>',
                'privacy' => '<a href="'.e(url('/privacy/')).'">'.e(__('auth.privacy')).'</a>',
            ]); ?></p>
        </section>

        <div class="login-art" aria-hidden="true">
            <svg viewBox="0 0 340 220" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="64" cy="48" r="16"/><path d="M40 190 H320"/><path d="M52 190 C110 130 190 138 250 128 C268 126 280 122 300 118"/>
                <path d="M200 130 V90 L232 66 L264 90 V130 Z"/><path d="M222 130 V104 H242 V130"/>
            </svg>
            <p class="t-aside"><?php echo e(__('auth.login_aside')); ?></p>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $attributes = $__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__attributesOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd)): ?>
<?php $component = $__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd; ?>
<?php unset($__componentOriginal8c0e86a062c1c5bb6d0e151b7076f3fd); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/auth/login.blade.php ENDPATH**/ ?>