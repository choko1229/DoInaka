@props([
    'href',
    'title',
    'date' => null,
    'area' => null,
    'tags' => [],
    'status' => 'scheduled',
    'photo' => null,
    'alt' => '',
    'illust' => null,
])
<a class="event-card" href="{{ $href }}">
    <div class="event-card-photo">
        @if ($photo)
            <img src="{{ $photo }}" alt="{{ $alt }}" loading="lazy" decoding="async">
        @elseif ($illust)
            <x-illust-image :illust="$illust" :alt="''" />
            <span class="event-card-wanted">{{ __('illust.wanted') }}</span>
        @endif
    </div>
    <div class="event-card-body">
        @if ($date || $status !== 'scheduled')
            <div>
                @if ($date || $status === 'cancelled')<span class="event-card-date {{ $status === 'cancelled' ? 'is-cancelled' : '' }}">@if ($status === 'cancelled'){{ __('layout.status_cancelled') }}@if ($date)・@endif @endif{{ $date }}</span>@endif
                @if ($status === 'ended')<span class="event-card-status">{{ __('layout.status_ended') }}</span>@endif
                @if ($status === 'postponed')<span class="event-card-status postponed">{{ __('layout.status_postponed') }}</span>@endif
                @if ($status === 'undecided')<span class="event-card-status">{{ __('layout.status_undecided') }}</span>@endif
            </div>
        @endif
        <span class="event-card-title">{{ $title }}</span>
        @if ($area)<span class="t-small t-muted">{{ $area }}</span>@endif
        @if ($tags !== [])
            <span class="event-card-tags">@foreach ($tags as $tag)<span class="event-card-tag">{{ $tag }}</span>@endforeach</span>
        @endif
    </div>
</a>
