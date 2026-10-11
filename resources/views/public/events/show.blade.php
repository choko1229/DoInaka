@php
    $links = app(\App\Services\Url\PublicLinks::class);
    $status = $event->displayStatus();
    $ended = $event->status->value === 'ended' || $status === 'ended';
    $areaName = trim((($event->region?->parent?->parent_id !== null ? $event->region->parent->name : '')).' '.($event->region?->name ?? ''));
    $firstSource = $event->sources->first();
    $roots = $comments->filter(fn ($c) => $c->thread_id === null || $c->thread_id === $c->id);
    $replies = $comments->reject(fn ($c) => $c->thread_id === null || $c->thread_id === $c->id)->groupBy('thread_id');
@endphp
<x-layouts.public :meta="$meta" :pref="$pref" current="events">
    <article class="detail">
        <div class="detail-grid">
            <div class="detail-main">
                <header class="detail-head">
                    <p class="detail-labels">
                        @if ($event->category)<span class="tag-chip">{{ $event->category->name }}</span>@endif
                        @foreach ($event->tags as $tag)<a class="tag-chip" href="/{{ $pref }}/events/?tag={{ urlencode($tag->name) }}">{{ $tag->name }}</a>@endforeach
                        @if ($status === 'cancelled')<span class="tag-chip is-alert">{{ __('layout.status_cancelled') }}</span>@endif
                        @if ($event->is_postponed)<span class="tag-chip is-alert">{{ __('layout.status_postponed') }}</span>@endif
                        @if ($ended)<span class="tag-chip">{{ __('public.ended') }}</span>@endif
                    </p>
                    <h1 class="detail-title">{{ $event->title }}</h1>
                    @if ($areaName !== '' || $event->series)
                        <p class="detail-sub">{{ $areaName }}@if ($areaName !== '' && $event->series)・@endif @if ($event->series)<a href="{{ $links->series($event->series) }}">{{ __('public.past_editions') }}</a>@endif</p>
                    @endif
                </header>

                {{-- 情報元は、タイトルのすぐ下に大きく出す(設計書9.7) --}}
                <section class="sources" aria-label="{{ __('public.sources') }}">
                    <h2 class="sources-label">{{ __('public.sources') }}</h2>
                    <ul>
                        @foreach ($event->sources as $source)
                            <li>
                                @if ($source->url && preg_match('#^https?://#i', $source->url) === 1)
                                    <a href="{{ $source->url }}" rel="nofollow noopener" target="_blank">{{ $source->title ?: $source->url }}</a>
                                @else
                                    <span>{{ $source->title ?: $source->kind->label() }}</span>
                                @endif
                                <span class="pill">{{ $source->kind->label() }}</span>
                                @if ($source->kind === \App\Enums\EventSourceKind::Flyer && $source->media && $source->media->isProcessed())
                                    <a href="{{ \App\Support\MediaUrl::large($source->media) }}" target="_blank" rel="noopener"><img class="source-flyer" src="{{ \App\Support\MediaUrl::small($source->media) }}" alt="{{ $source->title ?: __('public.flyer_alt') }}" loading="lazy"></a>
                                @endif
                                
                            </li>
                        @endforeach
                    </ul>
                    <p class="t-small t-muted sources-note">@if ($firstSource?->checked_at){{ __('public.checked_on', ['date' => $firstSource->checked_at->isoFormat('M月D日')]) }}@endif{{ __('public.made_from_sources') }} <a href="/report/event/{{ $event->id }}/">{{ __('public.report_difference') }}</a></p>
                </section>

                @if ($ended)
                    <p class="alert">{{ __('public.ended_notice') }}
                        @if ($nextEvent)<a href="{{ $links->event($nextEvent) }}">{{ __('public.next_edition', ['date' => \App\Support\DateText::range($nextEvent)]) }}</a>@endif
                    </p>
                @endif

                <figure class="detail-photo">
                    <x-media-gallery :media="$event->media" :title="$event->title" />
                    @if ($event->media->isEmpty())
                        <div class="detail-photo-empty" role="img" aria-label="{{ __('illust.wanted') }}"><span>{{ __('illust.wanted') }}</span></div>
                    @endif
                    <figcaption class="detail-photo-caption">
                        <span>@if ($event->media->isNotEmpty()){{ __('public.photos_count', ['count' => $event->media->count()]) }}@endif</span>
                        <a href="/post/photo/event/{{ $event->id }}/">{{ __('public.post_photo') }}</a>
                    </figcaption>
                </figure>

                @if ($event->body)
                    <section class="detail-section prose"><h2 class="detail-h2">{{ __('public.about_event') }}</h2><p>{!! nl2br(e($event->body)) !!}</p></section>
                @endif

                <section class="detail-section" id="comments">
                    <h2 class="detail-h2">{{ __('public.comments') }}@if ($comments->isNotEmpty()) {{ __('public.comments_count', ['count' => $comments->count()]) }}@endif</h2>
                    <x-comment-form type="event" :id="$event->id" />
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

                @php $more = collect()->concat($nearbySpots->take(2))->concat($sameSeries->take(2))->concat($nearbyEvents->take(2))->take(3); @endphp
                @if ($more->isNotEmpty())
                    <section class="detail-section"><h2 class="detail-h2">{{ __('public.nearby_title') }}</h2>
                        <div class="card-grid detail-more">@foreach ($more as $item)<x-content-card :item="$item" />@endforeach</div>
                    </section>
                @endif
            </div>

            <aside class="detail-side">
                <x-ad position="event_detail" />
                <div class="detail-actions">
                    <x-reaction-buttons type="event" :id="$event->id" :favorite-count="\App\Models\Favorite::query()->where('favoritable_type', 'event')->where('favoritable_id', $event->id)->count()" :visit-count="\App\Models\Visit::query()->where('visitable_type', 'event')->where('visitable_id', $event->id)->count()" />
                    <x-share-buttons :url="$shareUrl" :title="$event->title" />
                </div>

                <section class="card side-card">
                    <h2 class="detail-h2">{{ __('public.schedule_title') }}</h2>
                    <ul class="schedule">
                        @foreach ($event->schedules as $s)
                            <li @class(['is-cancelled' => $s->is_cancelled])>
                                <strong>{{ \App\Support\DateText::day($s->date) }}</strong>
                                <span>@if ($s->note){{ $s->note }} @endif{{ \App\Support\DateText::time($s) }}@if ($s->is_cancelled) <span class="pill pill-failed">{{ __('layout.status_cancelled') }}</span>@endif</span>
                            </li>
                        @endforeach
                        @if ($event->schedules->isEmpty())<li class="t-muted">{{ __('public.schedule_undecided') }}</li>@endif
                    </ul>
                    <dl class="facts">
                        @if ($event->venue_name)<dt>{{ __('public.venue') }}</dt><dd>{{ $event->venue_name }}</dd>@endif
                        @if ($event->address)<dt>{{ __('public.address') }}</dt><dd>{{ $event->address }}</dd>@endif
                        @if ($event->fee)<dt>{{ __('public.fee') }}</dt><dd>{{ $event->fee }}</dd>@endif
                        @if ($firstSource)
                            <dt>{{ __('public.sources') }}</dt>
                            <dd>@if ($firstSource->url && preg_match('#^https?://#i', $firstSource->url) === 1)<a href="{{ $firstSource->url }}" rel="nofollow noopener" target="_blank">{{ $firstSource->title ?: $firstSource->url }}</a>@else{{ $firstSource->title ?: $firstSource->kind->label() }}@endif
                                @if ($firstSource->checked_at) {{ __('public.checked_on_short', ['date' => $firstSource->checked_at->isoFormat('M月D日')]) }}@endif</dd>
                        @endif
                        @if ($event->url && preg_match('#^https?://#i', $event->url) === 1)
                            <dt>{{ __('public.official_site') }}</dt><dd><a href="{{ $event->url }}" rel="nofollow noopener" target="_blank">{{ $event->url }}</a></dd>
                        @endif
                    </dl>
                    <p class="t-small"><a href="{{ rtrim($links->event($event), '/') }}/calendar.ics">{{ __('public.add_calendar') }}</a></p>
                    <p class="t-small t-muted">{{ __('public.schedule_may_change') }}</p>
                </section>

                @if ($event->lat !== null && $event->lng !== null)
                    <div class="map side-map" data-map data-lat="{{ $event->lat }}" data-lng="{{ $event->lng }}" data-title="{{ $event->title }}" role="img" aria-label="{{ __('public.map_of', ['name' => $event->title]) }}"></div>
                    <p class="t-small side-maplink"><a href="https://www.google.com/maps/search/?api=1&amp;query={{ $event->lat }},{{ $event->lng }}" rel="noopener" target="_blank">{{ __('public.open_map_app') }}</a></p>
                @endif
                <p class="t-small side-links"><a href="/report/event/{{ $event->id }}/">{{ __('public.report_wrong') }}</a></p>
                @if ($event->series)
                    <p class="t-small side-links"><a href="{{ $links->series($event->series) }}">{{ __('public.series_all', ['title' => $event->series->title]) }}</a></p>
                @endif
            </aside>
        </div>
    </article>
</x-layouts.public>
