<x-layouts.public :meta="$meta" :pref="$pref" current="articles">
    <article class="detail detail-narrow">
        <h1 class="t-display">{{ $article->title }}</h1>
        <p class="t-small t-muted">{{ $article->region->name }}@if ($article->published_at) · {{ $article->published_at->format('Y-m-d') }}@endif</p>
        <x-media-gallery :media="$article->media" :title="$article->title" />
        @if ($article->body)<div class="prose"><p>{!! nl2br(e($article->body)) !!}</p></div>@endif
        @if ($article->tags->isNotEmpty())
            <p class="tags">@foreach ($article->tags as $tag)<a class="chip" href="/{{ $pref }}/articles/?tag={{ urlencode($tag->name) }}">{{ $tag->name }}</a>@endforeach</p>
        @endif
        <x-ad position="article_detail" />
        <x-reaction-buttons type="article" :id="$article->id" :favorite-count="\App\Models\Favorite::query()->where('favoritable_type', 'article')->where('favoritable_id', $article->id)->count()" />
        <x-share-buttons :url="$shareUrl" :title="$article->title" />
    </article>
</x-layouts.public>