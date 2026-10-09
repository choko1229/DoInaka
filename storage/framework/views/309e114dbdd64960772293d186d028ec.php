<?php ($siteKey = app(\App\Services\Setting\SettingsService::class)->string(\App\Enums\SettingKey::TurnstileSiteKey)); ?>

<div class="visually-hidden" aria-hidden="true">
    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label>
</div>
<?php if($siteKey !== ''): ?>
    <div class="cf-turnstile" data-sitekey="<?php echo e($siteKey); ?>"></div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<?php $__currentLoopData = ['turnstile', 'rate_limit', 'urls', 'ng_word', 'website']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php $__errorArgs = [$key];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="alert alert-danger" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php /**PATH /var/www/html/resources/views/components/turnstile.blade.php ENDPATH**/ ?>