
<div class="admin-bar" role="region" aria-label="<?php echo e(__('public.admin_bar')); ?>">
    <a class="admin-bar-link" href="<?php echo e(url('/admin')); ?>"><?php echo e(__('layout.admin')); ?></a>
    <span class="admin-bar-item"><?php echo e(__('public.bar_submissions')); ?> <?php echo e($pendingSubmissions); ?></span>
    <span class="admin-bar-item"><?php echo e(__('public.bar_corrections')); ?> <?php echo e($pendingCorrections); ?></span>
    <details class="admin-bar-menu">
        <summary><?php echo e(__('public.bar_new')); ?></summary>
        <a href="<?php echo e(route('admin.events.create')); ?>"><?php echo e(__('public.nav_events')); ?></a>
        <a href="<?php echo e(route('admin.spots.create')); ?>"><?php echo e(__('public.nav_spots')); ?></a>
        <a href="<?php echo e(route('admin.articles.create')); ?>"><?php echo e(__('public.nav_articles')); ?></a>
    </details>
    <span class="admin-bar-item"><?php echo e(__('public.bar_ai_today', ['count' => $aiToday])); ?></span>

    <?php if($target): ?>
        <details class="admin-bar-menu admin-bar-page">
            <summary><?php echo e(__('public.bar_this_page')); ?></summary>
            <?php if($target instanceof \App\Models\Event): ?>
                <a href="<?php echo e(route('admin.events.edit', $target)); ?>"><?php echo e(__('public.bar_edit')); ?></a>
                <a href="<?php echo e(route('admin.events.edit', $target)); ?>#sources"><?php echo e(__('public.bar_reread_sources')); ?></a>
                <a href="<?php echo e(route('admin.revisions', ['type' => 'event', 'id' => $target->id])); ?>"><?php echo e(__('public.bar_history')); ?></a>
                <form method="post" action="<?php echo e(route('admin.events.cancel', $target)); ?>" data-confirm="<?php echo e(__('public.bar_confirm_cancel')); ?>"><?php echo csrf_field(); ?><button type="submit"><?php echo e(__('public.bar_cancel_event')); ?></button></form>
                <form method="post" action="<?php echo e(route('admin.bar.unpublish', ['type' => 'event', 'id' => $target->id])); ?>" data-confirm="<?php echo e(__('public.bar_confirm_unpublish')); ?>"><?php echo csrf_field(); ?><input type="hidden" name="return" value="<?php echo e($returnUrl); ?>"><button type="submit"><?php echo e(__('public.bar_unpublish')); ?></button></form>
            <?php elseif($target instanceof \App\Models\Spot): ?>
                <a href="<?php echo e(route('admin.spots.edit', $target)); ?>"><?php echo e(__('public.bar_edit')); ?></a>
                <a href="<?php echo e(route('admin.revisions', ['type' => 'spot', 'id' => $target->id])); ?>"><?php echo e(__('public.bar_history')); ?></a>
                <form method="post" action="<?php echo e(route('admin.bar.unpublish', ['type' => 'spot', 'id' => $target->id])); ?>" data-confirm="<?php echo e(__('public.bar_confirm_unpublish')); ?>"><?php echo csrf_field(); ?><input type="hidden" name="return" value="<?php echo e($returnUrl); ?>"><button type="submit"><?php echo e(__('public.bar_unpublish')); ?></button></form>
            <?php elseif($target instanceof \App\Models\Article): ?>
                <a href="<?php echo e(route('admin.articles.edit', $target)); ?>"><?php echo e(__('public.bar_edit')); ?></a>
                <a href="<?php echo e(route('admin.revisions', ['type' => 'article', 'id' => $target->id])); ?>"><?php echo e(__('public.bar_history')); ?></a>
                <form method="post" action="<?php echo e(route('admin.bar.unpublish', ['type' => 'article', 'id' => $target->id])); ?>" data-confirm="<?php echo e(__('public.bar_confirm_unpublish')); ?>"><?php echo csrf_field(); ?><input type="hidden" name="return" value="<?php echo e($returnUrl); ?>"><button type="submit"><?php echo e(__('public.bar_unpublish')); ?></button></form>
            <?php elseif($target instanceof \App\Models\Region): ?>
                <form method="post" action="<?php echo e(route('admin.bar.regenerate', $target)); ?>"><?php echo csrf_field(); ?><input type="hidden" name="return" value="<?php echo e($returnUrl); ?>"><button type="submit"><?php echo e(__('public.bar_regenerate_intro')); ?></button></form>
                <a href="<?php echo e(route('admin.masters.regions.edit', $target)); ?>"><?php echo e(__('public.bar_edit')); ?></a>
                <a href="<?php echo e(route('admin.revisions', ['type' => 'region', 'id' => $target->id])); ?>"><?php echo e(__('public.bar_history')); ?></a>
            <?php endif; ?>
        </details>
    <?php endif; ?>
    <form class="admin-bar-logout" method="post" action="<?php echo e(url('/logout')); ?>"><?php echo csrf_field(); ?><button type="submit"><?php echo e($user?->name); ?> · <?php echo e(__('public.bar_logout')); ?></button></form>
</div><?php /**PATH /var/www/html/resources/views/admin/bar.blade.php ENDPATH**/ ?>