<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['overseas' => false]));

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

foreach (array_filter((['overseas' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="consent">
    <p class="t-small"><?php echo __('submission.consent_terms', [
        'terms' => '<a href="'.e(url('/terms/')).'" target="_blank" rel="noopener">'.e(__('public.terms')).'</a>',
        'privacy' => '<a href="'.e(url('/privacy/')).'" target="_blank" rel="noopener">'.e(__('public.privacy')).'</a>',
    ]); ?></p>
    <label class="check-row">
        <input type="checkbox" name="consent_terms" value="1" required <?php if(old('consent_terms')): echo 'checked'; endif; ?>>
        <span><?php echo e(__('submission.consent_terms_label')); ?></span>
    </label>
    <?php $__errorArgs = ['consent_terms'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    <?php if($overseas): ?>
        <label class="check-row">
            <input type="checkbox" name="consent_overseas" value="1" required <?php if(old('consent_overseas')): echo 'checked'; endif; ?>>
            <span><?php echo __('submission.consent_overseas_label', ['privacy' => '<a href="'.e(url('/privacy/')).'#overseas" target="_blank" rel="noopener">'.e(__('public.privacy')).'</a>']); ?></span>
        </label>
        <?php $__errorArgs = ['consent_overseas'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    <?php endif; ?>
</div><?php /**PATH /var/www/html/resources/views/components/consent.blade.php ENDPATH**/ ?>