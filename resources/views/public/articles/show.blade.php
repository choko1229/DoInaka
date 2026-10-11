<x-layouts.public :meta="$meta" :pref="$pref" current="articles">
    <article class="detail detail-narrow">
        <header class="detail-head">
            @if ($article->tags->isNotEmpty())
                <p class="detail-labels">@foreach ($article->tags as $tag)<a class="tag-chip" href="/{{ $pref }}/articles/?tag={{ urlencode($tag->name) }}">{{ $tag->name }}</a>@endforeach</p>
            @endif
            <h1 class="detail-title">{{ $article->title }}</h1>
            <p class="detail-sub">{{ $article->region->name }}@if ($article->published_at)・{{ $article->published_at->format('Y-m-d') }}@endif
                @if ($article->author && ! $article->is_anonymous && $article->author->status === \App\Enums\UserStatus::Active)<span class="detail-author">・{{ __('public.posted_by') }}: <a href="/users/{{ $article->author->id }}/">{{ $article->author->name }}</a></span>@else<span class="detail-author">・{{ __('public.posted_by') }}: {{ __('public.anonymous') }}</span>@endif</p>
        </header>
        <x-media-gallery :media="$article->media" :title="$article->title" />
        @if ($article->body)<div class="prose"><p>{!! nl2br(e($article->body)) !!}</p></div>@endif
        @if ($related !== [])
            <section class="detail-section region-wide"><h2 class="detail-h2">{{ __('public.article_related') }}</h2>
                <div class="card-grid detail-more">@foreach ($related as $item)<x-content-card :item="$item" />@endforeach</div>
            </section>
        @endif
        <x-ad position="article_detail" />
        <div class="detail-actions">
            <x-reaction-buttons type="article" :id="$article->id" :favorite-count="\App\Models\Favorite::query()->where('favoritable_type', 'article')->where('favoritable_id', $article->id)->count()" />
            <x-share-buttons :url="$shareUrl" :title="$article->title" />
        </div>
    </article>
</x-layouts.public>
