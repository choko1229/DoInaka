@props(['paginator'])
@if ($paginator->hasPages())
    <nav class="pager" aria-label="{{ __('public.pager') }}">
        @if ($paginator->onFirstPage())
            <span class="chip" aria-disabled="true">{{ __('public.prev') }}</span>
        @else
            <a class="chip" rel="prev" href="{{ $paginator->previousPageUrl() }}">{{ __('public.prev') }}</a>
        @endif
        <span class="t-small t-muted">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a class="chip" rel="next" href="{{ $paginator->nextPageUrl() }}">{{ __('public.next') }}</a>
        @else
            <span class="chip" aria-disabled="true">{{ __('public.next') }}</span>
        @endif
    </nav>
@endif