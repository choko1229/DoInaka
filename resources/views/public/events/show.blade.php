@php
    $links = app(\App\Services\Url\PublicLinks::class);
    $status = $event->displayStatus();
    $ended = $event->status->value === 'ended' || $status === 'ended';
@endphp
<x-layouts.public :meta="$meta" :pref="$pref" current="events">
    <article class="detail">
        <header>
            <p class="detail-labels">
                @if ($event->category)<span class="pill">{{ $event->category->name }}</span>@endif
                @if ($status === 'cancelled')<span class="pill pill-failed">{{ __('layout.status_cancelled') }}</span>@endif
                @if ($event->is_postponed)<span class="pill pill-warning">{{ __('layout.status_postponed') }}</span>@endif
                @if ($ended)<span class="pill">{{ __('public.ended') }}</span>@endif
            </p>
            <h1 class="t-display">{{ $event->title }}</h1>

            {{-- 情報元は、タイトルのすぐ下に大きく出す(設計書9.7) --}}
            <section class="sources" aria-label="{{ __('public.sources') }}">
                <h2 class="t-h3">{{ __('public.sources') }}</h2>
                <ul>
                    @foreach ($event->sources as $source)
                        <li>
                            <span class="pill">{{ $source->kind->label() }}</span>
                            @if ($source->url && preg_match('#^https?://#i', $source->url) === 1)
                                <a href="{{ $source->url }}" rel="nofollow noopener" target="_blank">{{ $source->title ?: $source->url }}</a>
                            @else
                                <span>{{ $source->title ?: $source->kind->label() }}</span>
                            @endif
                            @if ($source->checked_at)<span class="t-small t-muted">{{ __('public.checked_at', ['date' => $source->checked_at->format('Y-m-d')]) }}</span>@endif
                        </li>
                    @endforeach
                </ul>
            </section>
        </header>

        @if ($ended)
            <p class="alert">{{ __('public.ended_notice') }}
                @if ($nextEvent)<a href="{{ $links->event($nextEvent) }}">{{ __('public.next_edition', ['date' => \App\Support\DateText::range($nextEvent)]) }}</a>@endif
            </p>
        @endif

        <div class="detail-grid">
            <div class="detail-main">
                <section class="card">
                    <h2 class="t-h2">{{ __('public.schedule') }}</h2>
                    <ul class="schedule">
                        @foreach ($event->schedules as $s)
                            <li @class(['is-cancelled' => $s->is_cancelled])>
                                <strong>{{ \App\Support\DateText::day($s->date) }}</strong>
                                @if (\App\Support\DateText::time($s))<span>{{ \App\Support\DateText::time($s) }}</span>@endif
                                @if ($s->is_cancelled)<span class="pill pill-failed">{{ __('layout.status_cancelled') }}</span>@endif
                                @if ($s->note)<span class="t-small t-muted">{{ $s->note }}</span>@endif
                            </li>
                        @endforeach
                        @if ($event->schedules->isEmpty())<li class="t-muted">{{ __('public.schedule_undecided') }}</li>@endif
                    </ul>
                    <dl class="facts">
                        @if ($event->venue_name)<dt>{{ __('public.venue') }}</dt><dd>{{ $event->venue_name }}</dd>@endif
                        @if ($event->address)<dt>{{ __('public.address') }}</dt><dd>{{ $event->address }}</dd>@endif
                        @if ($event->fee)<dt>{{ __('public.fee') }}</dt><dd>{{ $event->fee }}</dd>@endif
                        @if ($event->url && preg_match('#^https?://#i', $event->url) === 1)
                            <dt>{{ __('public.official_site') }}</dt><dd><a href="{{ $event->url }}" rel="nofollow noopener" target="_blank">{{ $event->url }}</a></dd>
                        @endif
                    </dl>
                    @if ($event->lat !== null && $event->lng !== null)
                        <div class="map" data-map data-lat="{{ $event->lat }}" data-lng="{{ $event->lng }}" data-title="{{ $event->title }}" role="img" aria-label="{{ __('public.map_of', ['name' => $event->title]) }}"></div>
                    @endif
                </section>

                @if ($event->body)
                    <section class="card prose"><h2 class="t-h2">{{ __('public.about_event') }}</h2><p>{!! nl2br(e($event->body)) !!}</p></section>
                @endif

                @if ($event->tags->isNotEmpty())
                    <p class="tags">@foreach ($event->tags as $tag)<a class="chip" href="/{{ $pref }}/events/?tag={{ urlencode($tag->name) }}">{{ $tag->name }}</a>@endforeach</p>
                @endif

                <section class="card">
                    <h2 class="t-h2">{{ __('public.photos') }}</h2>
                    <p class="t-muted">{{ __('illust.wanted') }} — <a href="/post/">{{ __('public.post_photo') }}</a></p>
                </section>

                <section class="card" id="comments">
                    <h2 class="t-h2">{{ __('public.comments') }}</h2>
                    @forelse ($comments as $comment)
                        <div class="comment">
                            <p class="t-small t-muted">{{ $comment->user?->name ?? __('public.anonymous') }}@if ($comment->is_official) · {{ __('public.official') }}@endif</p>
                            <p>{!! nl2br(e($comment->body)) !!}</p>
                        </div>
                    @empty
                        <p class="t-muted">{{ __('public.no_comments') }}</p>
                    @endforelse
                </section>
            </div>

            <aside class="detail-side">
                <x-reaction-buttons type="event" :id="$event->id" :favorite-count="\App\Models\Favorite::query()->where('favoritable_type', 'event')->where('favoritable_id', $event->id)->count()" :visit-count="\App\Models\Visit::query()->where('visitable_type', 'event')->where('visitable_id', $event->id)->count()" />
                <x-share-buttons :url="$shareUrl" :title="$event->title" />
                <p class="t-small"><a href="/report/event/{{ $event->id }}/">{{ __('public.report_error') }}</a></p>
                @if ($event->series)
                    <p class="t-small"><a href="{{ $links->series($event->series) }}">{{ __('public.series_all', ['title' => $event->series->title]) }}</a></p>
                @endif
            </aside>
        </div>

        @if ($sameSeries->isNotEmpty())
            <section class="block"><h2 class="t-h1">{{ __('public.other_editions') }}</h2>
                <div class="card-grid">@foreach ($sameSeries->take(4) as $e)<x-content-card :item="$e" />@endforeach</div>
            </section>
        @endif
        @if ($nearbyEvents->isNotEmpty())
            <section class="block"><h2 class="t-h1">{{ __('public.nearby_events') }}</h2>
                <div class="card-grid">@foreach ($nearbyEvents as $e)<x-content-card :item="$e" />@endforeach</div>
            </section>
        @endif
        @if ($nearbySpots->isNotEmpty())
            <section class="block"><h2 class="t-h1">{{ __('public.nearby_spots') }}</h2>
                <div class="card-grid">@foreach ($nearbySpots as $s)<x-content-card :item="$s" />@endforeach</div>
            </section>
        @endif
    </article>
</x-layouts.public>