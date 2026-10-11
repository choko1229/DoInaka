@props(['paginator'])
{{-- ページ送り: 番号の丸(いまのページは塗りつぶし)。前後のリンクは、検索エンジンと支援技術のために、見えない形で残す --}}
@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        // 多いときは、いまのページの前後だけと、最初・最後(間は「…」)
        $pages = collect(range(1, $last))->filter(fn (int $p): bool => $p === 1 || $p === $last || abs($p - $current) <= 2)->values();
    @endphp
    <nav class="pager" aria-label="{{ __('public.pager') }}">
        @if (! $paginator->onFirstPage())<a class="visually-hidden" rel="prev" href="{{ $paginator->previousPageUrl() }}">{{ __('public.prev') }}</a>@endif
        @foreach ($pages as $i => $page)
            @if ($i > 0 && $page - $pages[$i - 1] > 1)<span class="pager-gap" aria-hidden="true">…</span>@endif
            @if ($page === $current)
                <span class="pager-num" aria-current="page">{{ $page }}</span>
            @else
                <a class="pager-num" href="{{ $paginator->url($page) }}" aria-label="{{ __('public.page_n', ['n' => $page]) }}">{{ $page }}</a>
            @endif
        @endforeach
        @if ($paginator->hasMorePages())<a class="visually-hidden" rel="next" href="{{ $paginator->nextPageUrl() }}">{{ __('public.next') }}</a>@endif
        {{-- スマホは、番号ではなく「もっと見る」(次のページへ) --}}
        @if ($paginator->hasMorePages())<a class="btn load-more" href="{{ $paginator->nextPageUrl() }}">{{ __('public.load_more') }}</a>@endif
    </nav>
@endif