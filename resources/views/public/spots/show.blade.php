<x-layouts.public :meta="$meta" :pref="$pref" current="spots">
    <article class="detail">
        <header>
            <p class="detail-labels">@if ($spot->category)<span class="pill">{{ $spot->category->name }}</span>@endif</p>
            <h1 class="t-display">{{ $spot->title }}</h1>
        </header>
        <div class="detail-grid">
            <div class="detail-main">
                <section class="card">
                    <dl class="facts">
                        @if ($spot->address)<dt>{{ __('public.address') }}</dt><dd>{{ $spot->address }}</dd>@endif
                        @if ($spot->hours)<dt>{{ __('public.hours') }}</dt><dd>{{ $spot->hours }}</dd>@endif
                        @if ($spot->access)<dt>{{ __('public.access') }}</dt><dd>{{ $spot->access }}</dd>@endif
                        @if ($spot->url && preg_match('#^https?://#i', $spot->url) === 1)
                            <dt>{{ __('public.official_site') }}</dt><dd><a href="{{ $spot->url }}" rel="nofollow noopener" target="_blank">{{ $spot->url }}</a></dd>
                        @endif
                    </dl>
                    @if ($spot->lat !== null && $spot->lng !== null)
                        <div class="map" data-map data-lat="{{ $spot->lat }}" data-lng="{{ $spot->lng }}" data-title="{{ $spot->title }}" role="img" aria-label="{{ __('public.map_of', ['name' => $spot->title]) }}"></div>
                    @endif
                </section>
                @if ($spot->body)<section class="card prose"><p>{!! nl2br(e($spot->body)) !!}</p></section>@endif
                @if ($spot->tags->isNotEmpty())
                    <p class="tags">@foreach ($spot->tags as $tag)<a class="chip" href="/{{ $pref }}/spots/?tag={{ urlencode($tag->name) }}">{{ $tag->name }}</a>@endforeach</p>
                @endif
                <section class="card" id="comments">
                    <h2 class="t-h2">{{ __('public.comments') }}</h2>
                    @forelse ($comments as $comment)
                        <div class="comment"><p class="t-small t-muted">{{ $comment->user?->name ?? __('public.anonymous') }}</p><p>{!! nl2br(e($comment->body)) !!}</p></div>
                    @empty
                        <p class="t-muted">{{ __('public.no_comments') }}</p>
                    @endforelse
                </section>
            </div>
            <aside class="detail-side">
                <x-reaction-buttons type="spot" :id="$spot->id" :favorite-count="\App\Models\Favorite::query()->where('favoritable_type', 'spot')->where('favoritable_id', $spot->id)->count()" :visit-count="\App\Models\Visit::query()->where('visitable_type', 'spot')->where('visitable_id', $spot->id)->count()" />
                <x-share-buttons :url="$shareUrl" :title="$spot->title" />
                <p class="t-small"><a href="/report/spot/{{ $spot->id }}/">{{ __('public.report_error') }}</a></p>
            </aside>
        </div>
        @if ($nearbyEvents->isNotEmpty())
            <section class="block"><h2 class="t-h1">{{ __('public.nearby_events') }}</h2><div class="card-grid">@foreach ($nearbyEvents as $e)<x-content-card :item="$e" />@endforeach</div></section>
        @endif
        @if ($nearbySpots->isNotEmpty())
            <section class="block"><h2 class="t-h1">{{ __('public.nearby_spots') }}</h2><div class="card-grid">@foreach ($nearbySpots as $s)<x-content-card :item="$s" />@endforeach</div></section>
        @endif
    </article>
</x-layouts.public>