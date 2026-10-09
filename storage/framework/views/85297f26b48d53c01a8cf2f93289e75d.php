<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['title' => __('masters.title'),'current' => 'masters']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('masters.title')),'current' => 'masters']); ?>
    <div class="page-head">
        <h1 class="t-h1"><?php echo e(__('masters.title')); ?></h1>
        <p class="t-small t-muted"><?php echo e(__('masters.lead')); ?></p>
    </div>

    <nav class="tabs" aria-label="<?php echo e(__('masters.title')); ?>">
        <?php $__currentLoopData = ['regions', 'categories', 'tags', 'ng']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('admin.masters', ['tab' => $t])); ?>" <?php if($tab === $t): ?> aria-current="page" <?php endif; ?>><?php echo e(__('masters.tab_'.$t)); ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </nav>

    <?php if($tab === 'regions'): ?>
        <form method="get" action="<?php echo e(route('admin.masters')); ?>" class="inline-form">
            <input type="hidden" name="tab" value="regions">
            <label for="pref" class="visually-hidden"><?php echo e(__('masters.pref_label')); ?></label>
            <select id="pref" name="pref" onchange="this.form.submit()">
                <?php $__currentLoopData = $prefectures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pref): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($pref->slug); ?>" <?php if($selected?->id === $pref->id): echo 'selected'; endif; ?>><?php echo e($pref->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <noscript><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?><?php echo e(__('masters.pref_label')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></noscript>
        </form>

        <?php if($selected): ?>
            <section class="card">
                <h2 class="t-h2"><?php echo e($selected->name); ?></h2>
                <form method="post" action="<?php echo e(route('admin.masters.regions.flags', $selected)); ?>" class="flags">
                    <?php echo csrf_field(); ?>
                    <label class="check-row"><input type="checkbox" name="accepts_posts" value="1" <?php if($selected->accepts_posts): echo 'checked'; endif; ?>><span><?php echo e(__('masters.col_accepts')); ?></span></label>
                    <label class="check-row"><input type="checkbox" name="crawl_enabled" value="1" <?php if($selected->crawl_enabled): echo 'checked'; endif; ?>><span><?php echo e(__('masters.col_crawl')); ?></span></label>
                    <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','size' => 'sm']); ?><?php echo e(__('masters.save')); ?> <?php echo $__env->renderComponent(); ?>
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
                <p class="t-caption t-muted"><?php echo e(__('masters.pref_flags_note')); ?></p>
            </section>

            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th><?php echo e(__('masters.col_name')); ?></th><th><?php echo e(__('masters.col_url')); ?></th><th><?php echo e(__('masters.col_public')); ?></th><th><?php echo e(__('masters.col_count')); ?></th><th></th></tr></thead>
                    <tbody>
                        <tr class="row-strong"><td><?php echo e($selected->name); ?></td><td>/<?php echo e($selected->slug); ?>/</td><td><?php echo e($selected->is_active ? '○' : '—'); ?></td><td><?php echo e($usage[$selected->id] ?? 0); ?></td><td><a href="<?php echo e(route('admin.masters.regions.edit', $selected)); ?>"><?php echo e(__('masters.edit')); ?></a></td></tr>
                        <?php $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="indent-1"><?php echo e($city->name); ?></td>
                                <td>/<?php echo e($selected->slug); ?>/<?php echo e($city->slug); ?>/</td>
                                <td><?php echo e($city->is_active ? '○' : '—'); ?></td>
                                <td><?php echo e($usage[$city->id] ?? 0); ?></td>
                                <td><a href="<?php echo e(route('admin.masters.regions.edit', $city)); ?>"><?php echo e(__('masters.edit')); ?></a></td>
                            </tr>
                            <?php $__currentLoopData = $olds[$city->id] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $old): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="row-old">
                                    <td class="indent-2"><?php echo e($old->name); ?> <span class="pill"><?php echo e($old->era?->label()); ?></span></td>
                                    <td>/<?php echo e($selected->slug); ?>/<?php echo e($city->slug); ?>/<?php echo e($old->slug); ?>/</td>
                                    <td><?php echo e($old->is_active ? '○' : '—'); ?></td>
                                    <td><?php echo e($usage[$old->id] ?? 0); ?></td>
                                    <td><a href="<?php echo e(route('admin.masters.regions.edit', $old)); ?>"><?php echo e(__('masters.edit')); ?></a></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php elseif($tab === 'categories'): ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?php echo e(__('masters.category_target')); ?></th><th><?php echo e(__('masters.col_name')); ?></th><th><?php echo e(__('masters.category_slug')); ?></th><th><?php echo e(__('masters.category_order')); ?></th><th><?php echo e(__('masters.category_active')); ?></th><th></th></tr></thead>
                <tbody>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <form method="post" action="<?php echo e(route('admin.masters.categories.update', $category)); ?>" id="cat-<?php echo e($category->id); ?>"><?php echo csrf_field(); ?></form>
                            <td><?php echo e($category->target->label()); ?></td>
                            <td><input form="cat-<?php echo e($category->id); ?>" name="name" value="<?php echo e($category->name); ?>" required maxlength="60"></td>
                            <td><?php echo e($category->slug); ?></td>
                            <td><input form="cat-<?php echo e($category->id); ?>" name="sort_order" type="number" min="0" max="9999" value="<?php echo e($category->sort_order); ?>" style="width:5rem"></td>
                            <td><input form="cat-<?php echo e($category->id); ?>" type="checkbox" name="is_active" value="1" <?php if($category->is_active): echo 'checked'; endif; ?>></td>
                            <td class="actions">
                                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','size' => 'sm','form' => 'cat-'.e($category->id).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','size' => 'sm','form' => 'cat-'.e($category->id).'']); ?><?php echo e(__('masters.save')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
                                <form method="post" action="<?php echo e(route('admin.masters.categories.destroy', $category)); ?>" style="display:inline"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','size' => 'sm']); ?><?php echo e(__('masters.delete')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <section class="card">
            <h2 class="t-h2"><?php echo e(__('masters.category_new')); ?></h2>
            <form method="post" action="<?php echo e(route('admin.masters.categories.store')); ?>" class="inline-form">
                <?php echo csrf_field(); ?>
                <select name="target" required><?php $__currentLoopData = \App\Enums\CategoryTarget::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $target): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($target->value); ?>"><?php echo e($target->label()); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
                <input name="name" placeholder="<?php echo e(__('masters.col_name')); ?>" required maxlength="60">
                <input name="slug" placeholder="<?php echo e(__('masters.category_slug')); ?>" required maxlength="60" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary']); ?><?php echo e(__('masters.add')); ?> <?php echo $__env->renderComponent(); ?>
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
            <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error" role="alert"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </section>
    <?php elseif($tab === 'tags'): ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?php echo e(__('masters.col_name')); ?></th><th><?php echo e(__('masters.tag_uses')); ?></th><th></th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($tag->name); ?></td>
                            <td><?php echo e($tagUsage[$tag->id] ?? 0); ?></td>
                            <td><form method="post" action="<?php echo e(route('admin.masters.tags.destroy', $tag)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','size' => 'sm']); ?><?php echo e(__('masters.delete')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></form></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="3" class="t-muted"><?php echo e(__('masters.empty')); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <section class="card">
            <h2 class="t-h2"><?php echo e(__('masters.tag_new')); ?></h2>
            <form method="post" action="<?php echo e(route('admin.masters.tags.store')); ?>" class="inline-form">
                <?php echo csrf_field(); ?>
                <input name="name" required maxlength="60" placeholder="<?php echo e(__('masters.col_name')); ?>">
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary']); ?><?php echo e(__('masters.add')); ?> <?php echo $__env->renderComponent(); ?>
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
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?php echo e(__('masters.ng_word')); ?></th><th><?php echo e(__('masters.ng_match')); ?></th><th></th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $ngWords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $word): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($word->word); ?></td>
                            <td><?php echo e($word->match_type->value); ?></td>
                            <td><form method="post" action="<?php echo e(route('admin.masters.ng.destroy', $word)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','size' => 'sm']); ?><?php echo e(__('masters.delete')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?></form></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="3" class="t-muted"><?php echo e(__('masters.empty')); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <section class="card">
            <h2 class="t-h2"><?php echo e(__('masters.ng_new')); ?></h2>
            <form method="post" action="<?php echo e(route('admin.masters.ng.store')); ?>" class="inline-form">
                <?php echo csrf_field(); ?>
                <input name="word" required maxlength="100" placeholder="<?php echo e(__('masters.ng_word')); ?>">
                <select name="match_type"><?php $__currentLoopData = \App\Enums\MatchType::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($type->value); ?>"><?php echo e($type->value); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary']); ?><?php echo e(__('masters.add')); ?> <?php echo $__env->renderComponent(); ?>
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
    <?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $attributes = $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $component = $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/masters/index.blade.php ENDPATH**/ ?>