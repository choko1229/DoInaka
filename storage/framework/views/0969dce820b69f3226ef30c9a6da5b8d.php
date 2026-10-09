<div class="tabs">
    <a href="<?php echo e(route('admin.review')); ?>" <?php if(($current ?? '') === 'review'): ?> aria-current="page" <?php endif; ?>><?php echo e(__('submission.tab_review')); ?> <?php echo e($counts['review']); ?></a>
    <a href="<?php echo e(route('admin.review', ['tab' => 'waiting'])); ?>" <?php if(($current ?? '') === 'waiting'): ?> aria-current="page" <?php endif; ?>><?php echo e(__('submission.tab_waiting')); ?> <?php echo e($counts['waiting']); ?></a>
    <a href="<?php echo e(route('admin.review.rejected')); ?>" <?php if(($current ?? '') === 'rejected'): ?> aria-current="page" <?php endif; ?>><?php echo e(__('submission.tab_rejected')); ?> <?php echo e($counts['rejected']); ?></a>
    <a href="<?php echo e(route('admin.corrections')); ?>" <?php if(($current ?? '') === 'corrections'): ?> aria-current="page" <?php endif; ?>><?php echo e(__('submission.tab_corrections')); ?></a>
    <a href="<?php echo e(route('admin.tips')); ?>" <?php if(($current ?? '') === 'tips'): ?> aria-current="page" <?php endif; ?>><?php echo e(__('submission.tab_tips')); ?></a>
</div><?php /**PATH /var/www/html/resources/views/admin/review/_tabs.blade.php ENDPATH**/ ?>