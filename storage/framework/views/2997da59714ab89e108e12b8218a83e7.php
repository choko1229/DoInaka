<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['type', 'id']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['type', 'id']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if(auth()->guard()->check()): ?>
    <form class="comment-form" method="post" action="<?php echo e(url("/api/v1/{$type}/{$id}/comments")); ?>">
        <?php echo csrf_field(); ?>
        <label for="comment-body" class="t-strong"><?php echo e(__('submission.comment_label')); ?></label>
        <textarea id="comment-body" name="body" rows="3" maxlength="500" required><?php echo e(old('body')); ?></textarea>
        <?php $__errorArgs = ['body'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
        <p class="t-small t-muted"><?php echo e(__('submission.comment_note')); ?></p>
        <div><button class="btn btn-sm" type="submit"><?php echo e(__('submission.comment_send')); ?></button></div>
    </form>
<?php else: ?>
    <p class="t-small"><a href="<?php echo e(url('/login/')); ?>"><?php echo e(__('submission.comment_login')); ?></a></p>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/components/comment-form.blade.php ENDPATH**/ ?>