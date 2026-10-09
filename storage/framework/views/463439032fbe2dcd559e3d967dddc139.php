<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('content.history'),'current' => 'contents']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('content.history')),'current' => 'contents']); ?>
    <div class="page-head">
        <h1 class="t-h1"><?php echo e(__('content.history')); ?></h1>
        <p class="t-small t-muted"><?php echo e($title); ?></p>
    </div>
    <p><a href="<?php echo e($back); ?>"><?php echo e(__('content.back_edit')); ?></a></p>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?php echo e(__('content.col_date')); ?></th><th><?php echo e(__('content.col_cause')); ?></th><th><?php echo e(__('content.col_actor')); ?></th><th><?php echo e(__('content.field_reason')); ?></th><th><?php echo e(__('content.col_diff')); ?></th><th></th></tr></thead>
            <tbody>
                <?php $__currentLoopData = $revisions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $revision): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $changed = [];
                        $before = $revision->before['attributes'] ?? [];
                        foreach (($revision->after['attributes'] ?? []) as $key => $value) {
                            if (($before[$key] ?? null) != $value) { $changed[] = $key; }
                        }
                        foreach (['schedules', 'sources', 'tags', 'relations'] as $relation) {
                            if (($revision->before[$relation] ?? null) != ($revision->after[$relation] ?? null) && $revision->before !== null) { $changed[] = $relation; }
                        }
                    ?>
                    <tr>
                        <td><?php echo e($revision->created_at?->setTimezone('Asia/Tokyo')->format('Y/n/j G:i')); ?></td>
                        <td><?php echo e($revision->cause->label()); ?></td>
                        <td><?php echo e($users[$revision->actor_user_id] ?? '—'); ?></td>
                        <td><?php echo e($revision->reason); ?></td>
                        <td class="t-small"><?php echo e($revision->before === null ? '' : implode(', ', $changed)); ?></td>
                        <td class="actions">
                            <?php if($revision->before !== null): ?>
                                <form method="post" action="<?php echo e(route('admin.revisions.rollback', $revision)); ?>" onsubmit="return confirm(<?php echo \Illuminate\Support\Js::from(__('content.rollback_confirm'))->toHtml() ?>)"><?php echo csrf_field(); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','size' => 'sm']); ?><?php echo e(__('content.rollback')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <p class="t-caption t-muted"><?php echo e(__('content.rollback_note')); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $attributes = $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $component = $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/revisions.blade.php ENDPATH**/ ?>