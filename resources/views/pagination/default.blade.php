@if ($paginator->hasPages())
    <nav class="pager-nav" role="navigation" aria-label="{{ __('pagination.label') }}">
        <ul class="pager">
            @if ($paginator->onFirstPage())
                <li><span class="pager-link is-disabled" aria-disabled="true">{{ __('pagination.previous') }}</span></li>
            @else
                <li><a class="pager-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('pagination.previous') }}</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="pager-link is-gap">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="pager-link is-current" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="pager-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a class="pager-link" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('pagination.next') }}</a></li>
            @else
                <li><span class="pager-link is-disabled" aria-disabled="true">{{ __('pagination.next') }}</span></li>
            @endif
        </ul>
        <p class="t-small t-muted">{{ __('pagination.summary', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}</p>
    </nav>
@endif