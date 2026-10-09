<?php
    $run = fn ($r) => $r;
    $bytes = fn (int $b): string => $b >= 1048576 ? number_format($b / 1048576, 0).'MB' : number_format(max($b, 1) / 1024, 0).'KB';
    $latest = $lastCheck['latest'] ?? null;
    $skipped = $lastCheck['skipped_beta'] ?? null;
?>
<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('admin.update'),'current' => 'update']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('admin.update')),'current' => 'update']); ?>
    <div class="page-head">
        <h1 class="t-h1"><?php echo e(__('admin.update')); ?></h1>
        <p class="t-small t-muted"><?php echo e(__('admin.update_lead')); ?></p>
    </div>

    <?php if (isset($component)) { $__componentOriginalcc2f0af263c948f4371398f1ec3bb808 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcc2f0af263c948f4371398f1ec3bb808 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-warnings','data' => ['warnings' => $warnings]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-warnings'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['warnings' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($warnings)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcc2f0af263c948f4371398f1ec3bb808)): ?>
<?php $attributes = $__attributesOriginalcc2f0af263c948f4371398f1ec3bb808; ?>
<?php unset($__attributesOriginalcc2f0af263c948f4371398f1ec3bb808); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcc2f0af263c948f4371398f1ec3bb808)): ?>
<?php $component = $__componentOriginalcc2f0af263c948f4371398f1ec3bb808; ?>
<?php unset($__componentOriginalcc2f0af263c948f4371398f1ec3bb808); ?>
<?php endif; ?>

    <div class="grid-2">
        <section class="card" aria-labelledby="version-title">
            <div class="card-head">
                <h2 class="t-h2" id="version-title"><?php echo e(__('admin.version')); ?></h2>
                <?php if($latest): ?><span class="pill pill-warning"><?php echo e(__('admin.version_new_badge')); ?></span><?php endif; ?>
            </div>
            <div class="version-row">
                <div>
                    <p class="t-caption t-muted"><?php echo e(__('admin.version_current')); ?></p>
                    <p class="t-h1"><?php echo e($current ?? __('admin.version_dev')); ?></p>
                </div>
                <?php if($latest): ?>
                    <span aria-hidden="true">→</span>
                    <div>
                        <p class="t-caption t-muted"><?php echo e(__('admin.version_latest')); ?><?php if(! empty($latest['published_at'])): ?>(<?php echo e(\Illuminate\Support\Carbon::parse($latest['published_at'])->setTimezone('Asia/Tokyo')->format('n/j')); ?>)<?php endif; ?></p>
                        <p class="t-h1 accent"><?php echo e($latest['prerelease'] ? '【BETA】' : ''); ?><?php echo e($latest['version']); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($latest && $latest['body'] !== ''): ?>
                <div class="notes">
                    <p class="t-strong"><?php echo e(__('admin.version_changes')); ?></p>
                    <div class="t-small" style="white-space:pre-line"><?php echo e($latest['body']); ?></div>
                    <?php if($latest['url'] !== ''): ?><p class="t-small"><a href="<?php echo e($latest['url']); ?>" rel="noopener noreferrer"><?php echo e(__('admin.version_release_link')); ?></a></p><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($skipped): ?>
                <p class="t-caption t-muted"><?php echo e(__('admin.version_skipped_beta', ['version' => '【BETA】'.$skipped['version'], 'date' => ! empty($skipped['published_at']) ? \Illuminate\Support\Carbon::parse($skipped['published_at'])->setTimezone('Asia/Tokyo')->format('n/j') : ''])); ?></p>
            <?php endif; ?>

            <div class="button-row">
                <?php if($latest && $current): ?>
                    <form method="post" action="<?php echo e(route('admin.update.apply')); ?>"><?php echo csrf_field(); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary']); ?><?php echo e(__('admin.version_update_now')); ?> <?php echo $__env->renderComponent(); ?>
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
                <form method="post" action="<?php echo e(route('admin.update.check')); ?>"><?php echo csrf_field(); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?><?php echo e(__('admin.version_check_now')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></form>
            </div>

            <p class="t-caption t-muted">
                <?php if($lastCheck): ?>
                    <?php echo e(__('admin.version_last_check', ['at' => \Illuminate\Support\Carbon::parse($lastCheck['at'])->setTimezone('Asia/Tokyo')->format('n/j G:i'), 'status' => __('admin.check_'.($lastCheck['status'] === 'success' ? 'success' : 'failed')), 'repository' => $repository])); ?>

                <?php else: ?>
                    <?php echo e(__('admin.version_never_checked', ['repository' => $repository])); ?>

                <?php endif; ?>
            </p>
        </section>

        <section class="card" aria-labelledby="auto-title">
            <h2 class="t-h2" id="auto-title"><?php echo e(__('admin.auto_title')); ?></h2>
            <form method="post" action="<?php echo e(route('admin.update.settings')); ?>">
                <?php echo csrf_field(); ?>
                <label class="check-row">
                    <input type="checkbox" name="auto" value="1" <?php if($auto): echo 'checked'; endif; ?>>
                    <span><span class="t-strong"><?php echo e(__('admin.auto_label')); ?></span><br><span class="t-small t-muted"><?php echo e(__('admin.auto_help')); ?></span></span>
                </label>
                <label class="check-row">
                    <input type="checkbox" name="accept_beta" value="1" <?php if($acceptBeta): echo 'checked'; endif; ?>>
                    <span><span class="t-strong"><?php echo e(__('admin.beta_label')); ?></span><br><span class="t-small t-muted"><?php echo e(__('admin.beta_help')); ?></span></span>
                </label>

                <h3 class="t-h3"><?php echo e(__('admin.window_title')); ?></h3>
                <figure class="bars" aria-label="<?php echo e(__('admin.window_chart')); ?>">
                    <div class="bars-plot">
                        <?php $__currentLoopData = $averages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hour => $avg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="bar <?php if($hour === $selectedHour): ?> bar-selected <?php endif; ?>" style="height: <?php echo e(max(4, (int) round($avg / $maxAverage * 100))); ?>%" title="<?php echo e($hour); ?><?php echo e(__('admin.window_hour_suffix')); ?>: <?php echo e(number_format($avg, 1)); ?>"></span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="bars-axis t-caption t-muted" aria-hidden="true"><span>0<?php echo e(__('admin.window_hour_suffix')); ?></span><span>6<?php echo e(__('admin.window_hour_suffix')); ?></span><span>12<?php echo e(__('admin.window_hour_suffix')); ?></span><span>18<?php echo e(__('admin.window_hour_suffix')); ?></span></div>
                </figure>

                <?php if($windowMode === 'fixed'): ?>
                    <p class="t-small"><?php echo e(__('admin.window_fixed_note', ['hour' => $fixedHour])); ?></p>
                <?php elseif($dataDays >= 28): ?>
                    <p class="t-small"><?php echo e(__('admin.window_summary', ['hour' => $selectedHour, 'avg' => number_format($averages[$selectedHour], 1)])); ?></p>
                <?php else: ?>
                    <p class="t-small"><?php echo e(__('admin.window_collecting', ['days' => 28, 'hour' => $fixedHour])); ?></p>
                <?php endif; ?>

                <fieldset class="field">
                    <label><input type="radio" name="window_mode" value="auto" <?php if($windowMode !== 'fixed'): echo 'checked'; endif; ?>> <?php echo e(__('admin.window_auto')); ?></label>
                    <label><input type="radio" name="window_mode" value="fixed" <?php if($windowMode === 'fixed'): echo 'checked'; endif; ?>> <?php echo e(__('admin.window_fixed')); ?></label>
                </fieldset>
                <div class="field">
                    <label for="fixed_hour"><?php echo e(__('admin.window_fixed_hour')); ?></label>
                    <select id="fixed_hour" name="fixed_hour">
                        <?php $__currentLoopData = range(0, 23); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($h); ?>" <?php if($h === $fixedHour): echo 'selected'; endif; ?>><?php echo e($h); ?><?php echo e(__('admin.window_hour_suffix')); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['fixed_hour'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?><?php echo e(__('admin.save')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            </form>
        </section>
    </div>

    <section class="card" aria-labelledby="flow-title">
        <h2 class="t-h2" id="flow-title"><?php echo e(__('admin.flow_title')); ?></h2>
        <ol class="flow">
            <?php $__currentLoopData = ['backup', 'download', 'maintenance', 'swap', 'migrate', 'health', 'publish']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e(__('update.step_'.$step)); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ol>
        <p class="t-small"><?php echo e(__('admin.flow_note')); ?></p>
    </section>

    <section aria-labelledby="history-title">
        <h2 class="t-h2" id="history-title" style="margin-top:var(--space-8)"><?php echo e(__('admin.history_title')); ?></h2>
        <?php if($runs->isEmpty()): ?>
            <p class="t-muted"><?php echo e(__('admin.history_empty')); ?></p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th><?php echo e(__('admin.history_time')); ?></th><th><?php echo e(__('admin.history_version')); ?></th><th><?php echo e(__('admin.history_result')); ?></th><th><?php echo e(__('admin.history_trigger')); ?></th><th><?php echo e(__('admin.history_detail')); ?></th></tr></thead>
                    <tbody>
                        <?php $__currentLoopData = $runs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($r->started_at?->setTimezone('Asia/Tokyo')->format('m/d G:i')); ?></td>
                                <td><?php echo e($r->version_from); ?> → <?php echo e($r->is_beta ? '【BETA】' : ''); ?><?php echo e($r->version_to); ?></td>
                                <td><span class="pill pill-<?php echo e($r->status->value); ?>"><?php echo e(__('admin.status_'.$r->status->value)); ?></span></td>
                                <td><?php echo e($r->trigger->value === 'manual' ? __('admin.trigger_manual_by', ['name' => $r->triggered_by]) : __('admin.trigger_auto')); ?></td>
                                <td class="t-small">
                                    <?php if($r->started_at && $r->finished_at && $r->status->value === 'success'): ?>
                                        <?php echo e(__('admin.seconds', ['seconds' => $r->started_at->diffInSeconds($r->finished_at)])); ?>

                                    <?php else: ?>
                                        <?php echo e(\Illuminate\Support\Str::limit((string) collect(explode("\n", (string) $r->log))->last(), 120)); ?>

                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <div class="grid-2">
        <section class="card" aria-labelledby="backup-title">
            <h2 class="t-h2" id="backup-title"><?php echo e(__('admin.backup_title')); ?></h2>
            <?php $__empty_1 = true; $__currentLoopData = $backups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <p class="backup-row"><span class="t-strong"><?php echo e($b['name']); ?></span><span class="t-small t-muted"><?php echo e(__('admin.backup_sizes', ['db' => $bytes($b['db_bytes']), 'code' => $bytes($b['code_bytes'])])); ?></span></p>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="t-muted"><?php echo e(__('admin.backup_empty')); ?></p>
            <?php endif; ?>
            <p class="t-caption t-muted"><?php echo e(__('admin.backup_note')); ?></p>
        </section>

        <section class="card" aria-labelledby="conn-title">
            <h2 class="t-h2" id="conn-title"><?php echo e(__('admin.connection_title')); ?></h2>
            <p class="t-strong"><?php echo e(__('admin.repository')); ?></p>
            <p><?php echo e($repository); ?> <span class="pill pill-success">公開</span></p>
            <p class="t-caption t-muted"><?php echo e(__('admin.repository_public')); ?></p>
            <form method="post" action="<?php echo e(route('admin.update.webhook')); ?>">
                <?php echo csrf_field(); ?>
                <div class="field">
                    <label for="webhook_url"><?php echo e(__('admin.webhook')); ?></label>
                    <input id="webhook_url" name="webhook_url" type="url" autocomplete="off" placeholder="<?php echo e(__('admin.webhook_placeholder')); ?>">
                    <p class="t-caption t-muted"><?php echo e($webhook === '' ? __('admin.webhook_unset') : __('admin.webhook_set')); ?></p>
                    <?php $__errorArgs = ['webhook_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="button-row">
                    <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?><?php echo e(__('admin.webhook_change')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
                </div>
            </form>
            <form method="post" action="<?php echo e(route('admin.update.webhook.test')); ?>" style="margin-top:var(--space-2)">
                <?php echo csrf_field(); ?>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?><?php echo e(__('admin.webhook_test')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            </form>
        </section>
    </div>

    <section class="card" aria-labelledby="recovery-title">
        <h2 class="t-h2" id="recovery-title"><?php echo e(__('admin.recovery_title')); ?></h2>
        <ul class="t-small">
            <li><?php echo e(__('admin.recovery_1')); ?></li>
            <li><?php echo e(__('admin.recovery_2')); ?></li>
            <li><?php echo e(__('admin.recovery_3')); ?></li>
        </ul>
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/update.blade.php ENDPATH**/ ?>