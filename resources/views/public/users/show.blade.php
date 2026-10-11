<x-layouts.public :meta="$meta">
    <article class="detail">
        <header class="detail-head">
            <p class="detail-labels"><span class="tag-chip">{{ __('public.author_label') }}</span></p>
            <h1 class="detail-title">{{ $author->name }}</h1>
            @if ($author->bio)<p class="detail-sub">{!! nl2br(e($author->bio)) !!}</p>@endif
            <p class="detail-sub">{{ __('public.author_count', ['count' => $author->approved_count]) }}</p>
        </header>

        @if ($spots->isNotEmpty())
            <section class="detail-section region-wide"><h2 class="detail-h2">{{ __('public.nav_spots') }}</h2>
                <div class="card-grid detail-more">@foreach ($spots as $item)<x-content-card :item="$item" />@endforeach</div>
            </section>
        @endif
        @if ($articles->isNotEmpty())
            <section class="detail-section region-wide"><h2 class="detail-h2">{{ __('public.nav_articles') }}</h2>
                <div class="card-grid detail-more">@foreach ($articles as $item)<x-content-card :item="$item" />@endforeach</div>
            </section>
        @endif
        @if ($spots->isEmpty() && $articles->isEmpty())
            <p class="t-muted">{{ __('public.author_none') }}</p>
        @endif
    </article>
</x-layouts.public>