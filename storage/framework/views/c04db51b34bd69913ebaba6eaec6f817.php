<div class="repeat-row">
    <div class="field"><label><?php echo e(__('content.col_date')); ?></label><input type="date" name="schedules[<?php echo e($i); ?>][date]" value="<?php echo e($row['date'] ?? ''); ?>"></div>
    <div class="field"><label><?php echo e(__('content.field_start')); ?></label><input type="time" name="schedules[<?php echo e($i); ?>][start_time]" value="<?php echo e($row['start_time'] ?? ''); ?>"></div>
    <div class="field"><label><?php echo e(__('content.field_end')); ?></label><input type="time" name="schedules[<?php echo e($i); ?>][end_time]" value="<?php echo e($row['end_time'] ?? ''); ?>"></div>
    <div class="field"><label><?php echo e(__('content.col_note')); ?></label><input name="schedules[<?php echo e($i); ?>][note]" value="<?php echo e($row['note'] ?? ''); ?>" maxlength="200"></div>
    <label class="check-row"><input type="checkbox" name="schedules[<?php echo e($i); ?>][is_cancelled]" value="1" <?php if(! empty($row['is_cancelled'])): echo 'checked'; endif; ?>><span><?php echo e(__('content.display_cancelled')); ?></span></label>
    <button type="button" class="link-button" data-repeat-remove><?php echo e(__('content.remove_row')); ?></button>
</div><?php /**PATH /var/www/html/resources/views/admin/events/partials/schedule-row.blade.php ENDPATH**/ ?>