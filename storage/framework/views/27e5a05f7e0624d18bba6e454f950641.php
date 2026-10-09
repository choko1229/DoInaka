<div class="repeat-row">
    <div class="field">
        <label><?php echo e(__('content.field_source_kind')); ?></label>
        <select name="sources[<?php echo e($i); ?>][kind]">
            <?php $__currentLoopData = \App\Enums\EventSourceKind::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kind): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($kind->value); ?>" <?php if(($row['kind'] ?? 'url') === $kind->value): echo 'selected'; endif; ?>><?php echo e($kind->label()); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <input type="hidden" name="sources[<?php echo e($i); ?>][media_id]" value="<?php echo e($row['media_id'] ?? ''); ?>">
    <?php if(! empty($row['media_id'])): ?><span class="t-small t-muted"><?php echo e(__('submission.flyer_media', ['id' => $row['media_id']])); ?></span><?php endif; ?>
    <div class="field"><label>URL</label><input type="url" name="sources[<?php echo e($i); ?>][url]" value="<?php echo e($row['url'] ?? ''); ?>" maxlength="500"></div>
    <div class="field"><label><?php echo e(__('content.field_source_title')); ?></label><input name="sources[<?php echo e($i); ?>][title]" value="<?php echo e($row['title'] ?? ''); ?>" maxlength="200"></div>
    <div class="field"><label><?php echo e(__('content.field_checked_at')); ?></label><input type="date" name="sources[<?php echo e($i); ?>][checked_at]" value="<?php echo e($row['checked_at'] ?? ''); ?>"></div>
    <label class="check-row"><input type="checkbox" name="sources[<?php echo e($i); ?>][is_official]" value="1" <?php if(! empty($row['is_official'])): echo 'checked'; endif; ?>><span><?php echo e(__('content.field_official')); ?></span></label>
    <button type="button" class="link-button" data-repeat-remove><?php echo e(__('content.remove_row')); ?></button>
</div><?php /**PATH /var/www/html/resources/views/admin/events/partials/source-row.blade.php ENDPATH**/ ?>