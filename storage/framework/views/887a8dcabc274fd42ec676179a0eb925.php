<?php ($counts = ['review' => \App\Models\Submission::query()->where('status', \App\Enums\SubmissionStatus::InReview)->count(), 'waiting' => 0, 'rejected' => \App\Models\Submission::query()->whereIn('status', [\App\Enums\SubmissionStatus::Rejected, \App\Enums\SubmissionStatus::AutoRejected])->count()]); ?>
<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('submission.tab_corrections'),'current' => 'review']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('submission.tab_corrections')),'current' => 'review']); ?>
    <div class="page-head"><h1 class="t-h1"><?php echo e(__('submission.tab_corrections')); ?></h1></div>
    <?php echo $__env->make('admin.review._tabs', ['current' => 'corrections', 'counts' => $counts], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?php echo e(__('submission.col_receipt')); ?></th><th><?php echo e(__('submission.target')); ?></th><th><?php echo e(__('submission.col_field')); ?></th><th><?php echo e(__('submission.now')); ?></th><th><?php echo e(__('submission.proposed')); ?></th><th></th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $submissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($c = $s->corrections->first()); ?>
                    <tr>
                        <td><code><?php echo e($s->receipt_no); ?></code></td>
                        <td><?php echo e($c?->target_type); ?> #<?php echo e($c?->target_id); ?></td>
                        <td><?php echo e($c ? __('submission.fields.'.$c->field) : ''); ?></td>
                        <td><?php echo e(\Illuminate\Support\Str::limit($current[$s->id] ?? '', 40)); ?></td>
                        <td><?php echo e(\Illuminate\Support\Str::limit($c?->proposed_value ?? '', 40)); ?><?php if($c?->source_url): ?><br><span class="t-small t-muted"><?php echo e(__('submission.has_source')); ?></span><?php endif; ?></td>
                        <td class="actions"><a class="btn btn-sm" href="<?php echo e(route('admin.review.show', $s)); ?>"><?php echo e(__('submission.open')); ?></a></td>
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/review/corrections.blade.php ENDPATH**/ ?>