<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => $submission->receipt_no,'current' => 'review']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($submission->receipt_no),'current' => 'review']); ?>
    <div class="page-head">
        <h1 class="t-h1"><?php echo e($submission->type->label()); ?> <code><?php echo e($submission->receipt_no); ?></code></h1>
        <a href="<?php echo e(route('admin.review')); ?>"><?php echo e(__('submission.back_to_list')); ?></a>
    </div>

    <dl class="facts">
        <dt><?php echo e(__('submission.col_status')); ?></dt><dd><?php echo e($submission->status->label()); ?><?php if($submission->reject_reason): ?> — <?php echo e($submission->reject_reason); ?><?php endif; ?></dd>
        <dt><?php echo e(__('submission.col_from')); ?></dt><dd><?php echo e($submission->user?->name ?? __('submission.anonymous')); ?><?php if($submission->user): ?> (<?php echo e(__('submission.approved_count', ['count' => $submission->user->approved_count])); ?>)<?php endif; ?></dd>
        <dt><?php echo e(__('submission.received_at')); ?></dt><dd><?php echo e($submission->created_at?->format('Y-m-d H:i')); ?></dd>
        <dt><?php echo e(__('submission.consent')); ?></dt><dd><?php echo e($submission->consented_at?->format('Y-m-d H:i')); ?>(<?php echo e($submission->terms_version); ?>)</dd>
        <?php if($submission->target_type): ?><dt><?php echo e(__('submission.target')); ?></dt><dd><?php echo e($submission->target_type); ?> #<?php echo e($submission->target_id); ?></dd><?php endif; ?>
        <?php if($submission->reviewer): ?><dt><?php echo e(__('submission.reviewer')); ?></dt><dd><?php echo e($submission->reviewer->name); ?> <?php echo e($submission->reviewed_at?->format('Y-m-d H:i')); ?></dd><?php endif; ?>
    </dl>

    <section class="card">
        <h2 class="t-h2"><?php echo e(__('submission.content')); ?></h2>
        <dl class="facts">
            <?php $__currentLoopData = ($submission->payload ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($key !== 'inspection' && ! is_array($value)): ?>
                    <dt><?php echo e(__('submission.attributes.'.$key) !== 'submission.attributes.'.$key ? __('submission.attributes.'.$key) : $key); ?></dt>
                    <dd><?php echo nl2br(e((string) $value)); ?></dd>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </dl>
        <?php if($correction): ?>
            <h3 class="t-h3"><?php echo e(__('submission.correction_diff')); ?></h3>
            <dl class="facts">
                <dt><?php echo e(__('submission.fields.'.$correction->field)); ?>(<?php echo e(__('submission.now')); ?>)</dt><dd><?php echo e($currentValue); ?></dd>
                <dt><?php echo e(__('submission.proposed')); ?></dt><dd><?php echo e($correction->proposed_value); ?></dd>
                <?php if($correction->source_url): ?><dt><?php echo e(__('submission.report_source')); ?></dt><dd><a href="<?php echo e($correction->source_url); ?>" rel="nofollow noopener" target="_blank"><?php echo e($correction->source_url); ?></a></dd><?php endif; ?>
            </dl>
        <?php endif; ?>
        <?php if(isset($submission->payload['inspection'])): ?>
            <p class="t-small"><?php echo e(__('submission.inspection', ['status' => __('submission.inspection_'.$submission->payload['inspection']['status'])])); ?><?php if($submission->payload['inspection']['candidate']): ?> <?php echo e(__('submission.inspection_candidate')); ?><?php endif; ?></p>
        <?php endif; ?>
    </section>

    <?php if($submission->media->isNotEmpty()): ?>
        <section class="card">
            <h2 class="t-h2"><?php echo e(__('submission.photos')); ?></h2>
            <div class="card-grid">
                <?php $__currentLoopData = $submission->media; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $media): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <figure>
                        <a href="<?php echo e(route('admin.media.original', $media)); ?>" target="_blank" rel="noopener">
                            <?php if($media->isProcessed()): ?>
                                <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url((string) $media->path_small)); ?>" alt="" loading="lazy" style="max-width:100%">
                            <?php else: ?>
                                <span class="t-small"><?php echo e(__('submission.original_only')); ?></span>
                            <?php endif; ?>
                        </a>
                        <figcaption class="t-small t-muted"><?php echo e($media->width); ?>×<?php echo e($media->height); ?><?php if($media->credit): ?> · <?php echo e($media->credit); ?><?php endif; ?> <?php if($media->rights_agreed_at): ?> · <?php echo e(__('submission.rights_ok')); ?><?php endif; ?></figcaption>
                    </figure>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="card">
        <?php if($canApprove): ?>
            <form method="post" action="<?php echo e(route('admin.review.approve', $submission)); ?>" class="inline-form"><?php echo csrf_field(); ?><button class="btn btn-primary" type="submit"><?php echo e(__('submission.approve')); ?></button></form>
            <form method="post" action="<?php echo e(route('admin.review.reject', $submission)); ?>" class="inline-form">
                <?php echo csrf_field(); ?>
                <input type="text" name="reason" maxlength="300" placeholder="<?php echo e(__('submission.reject_reason')); ?>" aria-label="<?php echo e(__('submission.reject_reason')); ?>">
                <button class="btn" type="submit"><?php echo e(__('submission.reject')); ?></button>
            </form>
        <?php endif; ?>
        <?php if($submission->status->isRejected()): ?>
            <form method="post" action="<?php echo e(route('admin.review.restore', $submission)); ?>" class="inline-form"><?php echo csrf_field(); ?><button class="btn" type="submit"><?php echo e(__('submission.restore')); ?></button></form>
        <?php endif; ?>
    </section>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $attributes = $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $component = $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/review/show.blade.php ENDPATH**/ ?>