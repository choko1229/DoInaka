<div data-results>
    @include('public.events.partials.toolbar', ['total' => $events->total(), 'view' => 'list', 'basePath' => $basePath ?? '/'.($pref ?? 'kagawa').'/events/'])
    @if ($events->isEmpty())
        <x-empty-state :title="__('public.no_results')" :aside="__('public.no_results_aside')">{{ __('public.no_results_body') }}</x-empty-state>
    @else
        <div class="card-grid">
            @foreach ($events as $event)
                <x-content-card :item="$event" />
            @endforeach
        </div>
        <x-pager :paginator="$events" />
    @endif
</div>