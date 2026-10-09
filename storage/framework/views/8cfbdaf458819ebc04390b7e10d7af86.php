<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('submission.review_title'),'current' => 'review']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('submission.review_title')),'current' => 'review']); ?>
    <div class="page-head">
        <h1 class="t-h1"><?php echo e(__('submission.review_title')); ?></h1>
        <p class="t-small t-muted"><?php echo e(__('submission.review_lead')); ?></p>
    </div>
    <?php echo $__env->make('admin.review._tabs', ['current' => $tab, 'counts' => $counts], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="tabs">
        <a href="<?php echo e(route('admin.review', ['tab' => $tab])); ?>" <?php if($type === null): ?> aria-current="page" <?php endif; ?>><?php echo e(__('submission.all_types')); ?></a>
        <?php $__currentLoopData = \App\Enums\SubmissionType::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($t !== \App\Enums\SubmissionType::Event): ?>
                <a href="<?php echo e(route('admin.review', ['tab' => $tab, 'type' => $t->value])); ?>" <?php if($type === $t): ?> aria-current="page" <?php endif; ?>><?php echo e($t->label()); ?></a>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?php echo e(__('submission.col_receipt')); ?></th><th><?php echo e(__('submission.col_type')); ?></th><th><?php echo e(__('submission.col_summary')); ?></th><th><?php echo e(__('submission.col_from')); ?></th><th><?php echo e(__('submission.col_status')); ?></th><th></th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $submissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><code><?php echo e($s->receipt_no); ?></code><br><span class="t-small t-muted"><?php echo e($s->created_at?->format('Y-m-d H:i')); ?></span></td>
                        <td><?php echo e($s->type->label()); ?><?php if($s->media_count): ?> <span class="t-small t-muted">(写真 <?php echo e($s->media_count); ?>)</span><?php endif; ?></td>
                        <td><?php echo e(\Illuminate\Support\Str::limit($s->text('title') ?? $s->text('body') ?? $s->text('source_url') ?? $s->text('proposed_value') ?? '', 60)); ?></td>
                        <td><?php echo e($s->user?->name ?? __('submission.anonymous')); ?></td>
                        <td><span class="pill"><?php echo e($s->status->label()); ?></span></td>
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/review/index.blade.php ENDPATH**/ ?>