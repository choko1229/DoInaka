<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['item']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['item']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $links = app(\App\Services\Url\PublicLinks::class);
    $isEvent = $item instanceof \App\Models\Event;
    $hints = array_values(array_filter([$item->category?->slug ?? null, $item->region?->slug ?? null]));
    $illust = app(\App\Services\Design\IllustSelector::class)->select($item->getMorphClass().$item->id, $hints, $themeContext->theme, $themeContext->season, now());
    $status = $isEvent ? $item->displayStatus() : 'scheduled';
    $tags = $item->tags->take(3)->pluck('name')->all();
?>
<?php if (isset($component)) { $__componentOriginal07bdbe031a4c57e4cd3488994f94e999 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal07bdbe031a4c57e4cd3488994f94e999 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.event-card','data' => ['href' => $links->for($item),'title' => $item->title,'date' => $isEvent ? \App\Support\DateText::range($item) : null,'area' => $item->region?->name,'tags' => $tags,'status' => $status,'illust' => $illust]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('event-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($links->for($item)),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->title),'date' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isEvent ? \App\Support\DateText::range($item) : null),'area' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->region?->name),'tags' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($tags),'status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status),'illust' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($illust)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal07bdbe031a4c57e4cd3488994f94e999)): ?>
<?php $attributes = $__attributesOriginal07bdbe031a4c57e4cd3488994f94e999; ?>
<?php unset($__attributesOriginal07bdbe031a4c57e4cd3488994f94e999); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal07bdbe031a4c57e4cd3488994f94e999)): ?>
<?php $component = $__componentOriginal07bdbe031a4c57e4cd3488994f94e999; ?>
<?php unset($__componentOriginal07bdbe031a4c57e4cd3488994f94e999); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/components/content-card.blade.php ENDPATH**/ ?>