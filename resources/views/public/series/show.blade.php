<x-layouts.public :meta="$meta" :pref="$pref" current="events">
    <h1 class="t-display">{{ $series->title }}</h1>
    @if ($series->summary)<p>{!! nl2br(e($series->summary)) !!}</p>@endif
    <p class="t-small t-muted">{{ $series->recurrence->label() }}</p>
    <h2 class="t-h1">{{ __('public.editions') }}</h2>
    <div class="card-grid">@foreach ($events as $event)<x-content-card :item="$event" />@endforeach</div>
</x-layouts.public>