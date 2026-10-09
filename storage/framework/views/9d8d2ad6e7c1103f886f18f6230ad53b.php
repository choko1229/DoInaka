<?php ($counts = ['review' => \App\Models\Submission::query()->where('status', \App\Enums\SubmissionStatus::InReview)->count(), 'waiting' => 0, 'rejected' => $submissions->total()]); ?>
<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('submission.tab_rejected'),'current' => 'review']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('submission.tab_rejected')),'current' => 'review']); ?>
    <div class="page-head"><h1 class="t-h1"><?php echo e(__('submission.tab_rejected')); ?></h1><p class="t-small t-muted"><?php echo e(__('submission.rejected_lead')); ?></p></div>
    <?php echo $__env->make('admin.review._tabs', ['current' => 'rejected', 'counts' => $counts], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?php echo e(__('submission.col_receipt')); ?></th><th><?php echo e(__('submission.col_type')); ?></th><th><?php echo e(__('submission.col_summary')); ?></th><th><?php echo e(__('submission.col_status')); ?></th><th><?php echo e(__('submission.days_left')); ?></th><th></th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $submissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><code><?php echo e($s->receipt_no); ?></code></td>
                        <td><?php echo e($s->type->label()); ?></td>
                        <td><?php echo e(\Illuminate\Support\Str::limit($s->text('title') ?? $s->text('body') ?? $s->text('source_url') ?? '', 50)); ?><br><span class="t-small t-muted"><?php echo e($s->reject_reason); ?></span></td>
                        <td><span class="pill"><?php echo e($s->status->label()); ?></span></td>
                        <td><?php echo e($s->expires_at ? max(0, (int) ceil(now()->diffInDays($s->expires_at, false))) : '—'); ?></td>
                        <td class="actions">
                            <a class="btn btn-sm" href="<?php echo e(route('admin.review.show', $s)); ?>"><?php echo e(__('submission.open')); ?></a>
                            <form method="post" action="<?php echo e(route('admin.review.restore', $s)); ?>" style="display:inline"><?php echo csrf_field(); ?><button class="btn btn-sm" type="submit"><?php echo e(__('submission.restore')); ?></button></form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="t-muted"><?php echo e(__('submission.review_empty')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo e($submissions->links()); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $attributes = $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $component = $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/review/rejected.blade.php ENDPATH**/ ?>