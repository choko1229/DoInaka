@php
    $links = app(\App\Services\Url\PublicLinks::class);
    $areaName = collect([$spot->region?->parent?->parent_id !== null ? $spot->region->parent->name : null, $spot->region?->name])->filter()->unique()->implode(' ');
    $visitCount = \App\Models\Visit::query()->where('visitable_type', 'spot')->where('visitable_id', $spot->id)->count();
    $roots = $comments->filter(fn ($c) => $c->thread_id === null || $c->thread_id === $c->id);
    $replies = $comments->reject(fn ($c) => $c->thread_id === null || $c->thread_id === $c->id)->groupBy('thread_id');
    $more = collect()->concat($nearbyEvents->take(2))->concat($nearbySpots->take(2))->take(3);
@endphp
<x-layouts.public :meta="$meta" :pref="$pref" current="spots">
    <article class="detail">
        <div class="detail-grid">
            <div class="detail-main">
                <header class="detail-head">
                    <p class="detail-labels">
                        @if ($spot->category)<span class="tag-chip">{{ $spot->category->name }}</span>@endif
                        @foreach ($spot->tags as $tag)<a class="tag-chip" href="/{{ $pref }}/spots/?tag={{ urlencode($tag->name) }}">{{ $tag->name }}</a>@endforeach
                    </p>
                    <h1 class="detail-title">{{ $spot->title }}</h1>
                    <p class="detail-sub">{{ $areaName }}@if ($areaName !== '')・@endif{{ __('public.visited') }} {{ __('public.visit_people', ['count' => $visitCount]) }}</p>
                </header>

                <figure class="detail-photo">
                    <x-media-gallery :media="$spot->media" :title="$spot->title" />
                    @if ($spot->media->isEmpty())
                        <div class="detail-photo-empty" role="img" aria-label="{{ __('illust.wanted') }}"><span>{{ __('illust.wanted') }}</span></div>
                    @endif
                    <figcaption class="detail-photo-caption">
                        <span>@if ($spot->media->isNotEmpty()){{ __('public.photos_count', ['count' => $spot->media->count()]) }}@endif</span>
                        <a href="/post/photo/spot/{{ $spot->id }}/">{{ __('public.post_photo') }}</a>
                    </figcaption>
                </figure>

                @if ($spot->body)
                    <section class="detail-section prose"><h2 class="detail-h2">{{ __('public.about_spot') }}</h2><p>{!! nl2br(e($spot->body)) !!}</p></section>
                @endif

                <section class="detail-section" id="comments">
                    <h2 class="detail-h2">{{ __('public.comments') }}@if ($comments->isNotEmpty()) {{ __('public.comments_count', ['count' => $comments->count()]) }}@endif</h2>
                    <x-comment-form type="spot" :id="$spot->id" />
                    @forelse ($roots as $comment)
                        @php $thread = $replies->get($comment->id, collect()); @endphp
                        <div class="comment">
                            <x-comment-body :comment="$comment" />
                            @if ($thread->isNotEmpty())
                                <details class="comment-replies" @if ($thread->count() === 1) open @endif>
                                    <summary>{{ __('public.replies_show', ['count' => $thread->count()]) }}</summary>
                                    @foreach ($thread as $reply)<x-comment-body :comment="$reply" :reply="true" />@endforeach
                                </details>
                            @endif
                        </div>
                    @empty
                        <p class="t-muted">{{ __('public.no_comments') }}</p>
                    @endforelse
                </section>

                @if ($more->isNotEmpty())
                    <section class="detail-section"><h2 class="detail-h2">{{ __('public.nearby_title') }}</h2>
                        <div class="card-grid detail-more">@foreach ($more as $item)<x-content-card :item="$item" />@endforeach</div>
                    </section>
                @endif
            </div>

            <aside class="detail-side">
                <x-ad position="spot_detail" />
                <div class="detail-actions">
                    <x-reaction-buttons type="spot" primary="visited" :id="$spot->id" :favorite-count="\App\Models\Favorite::query()->where('favoritable_type', 'spot')->where('favoritable_id', $spot->id)->count()" :visit-count="$visitCount" />
                    <x-share-buttons :url="$shareUrl" :title="$spot->title" />
                </div>

                <section class="card side-card">
                    <h2 class="detail-h2">{{ __('public.basic_info') }}</h2>
                    <dl class="facts">
                        @if ($spot->hours)<dt>{{ __('public.hours') }}</dt><dd>{{ $spot->hours }}</dd>@endif
                        @if ($spot->address)<dt>{{ __('public.address') }}</dt><dd>{{ $spot->address }}</dd>@endif
                        @if ($spot->access)<dt>{{ __('public.access') }}</dt><dd>{{ $spot->access }}</dd>@endif
                        @if ($spot->url && preg_match('#^https?://#i', $spot->url) === 1)
                            <dt>{{ __('public.official_site') }}</dt><dd><a href="{{ $spot->url }}" rel="nofollow noopener" target="_blank">{{ $spot->url }}</a></dd>
                        @endif
                    </dl>
                </section>

                @if ($spot->lat !== null && $spot->lng !== null)
                    <div class="map side-map" data-map data-lat="{{ $spot->lat }}" data-lng="{{ $spot->lng }}" data-title="{{ $spot->title }}" role="img" aria-label="{{ __('public.map_of', ['name' => $spot->title]) }}"></div>
                    <p class="t-small side-maplink"><a href="https://www.google.com/maps/search/?api=1&amp;query={{ $spot->lat }},{{ $spot->lng }}" rel="noopener" target="_blank">{{ __('public.open_map_app') }}</a></p>
                @endif
                <p class="t-small side-links"><a href="/report/spot/{{ $spot->id }}/">{{ __('public.report_wrong_spot') }}</a></p>
            </aside>
        </div>
    </article>
</x-layouts.public>
