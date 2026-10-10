<div data-results>
    @if ($events->isEmpty())
        <x-empty-state :title="__('public.no_results')" :aside="__('public.no_results_aside')">{{ __('public.no_results_body') }}</x-empty-state>
    @else
        <p class="t-small t-muted">{{ __('public.total', ['count' => $events->total()]) }}</p>
        <div class="card-grid">
            @foreach ($events as $event)
                <x-content-card :item="$event" />
            @endforeach
        </div>
        <x-pager :paginator="$events" />
    @endif
</div>