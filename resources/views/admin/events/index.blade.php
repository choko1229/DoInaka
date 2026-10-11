@php($today = now()->setTimezone('Asia/Tokyo')->toDateString())
<x-layouts.admin :title="__('content.events_title')" current="events">
    <div class="page-head">
        <h1 class="t-h1">{{ __('content.events_title') }}</h1>
        <p class="t-small t-muted">{{ __('content.events_lead') }}</p>
    </div>

    <form method="get" action="{{ route('admin.events') }}" class="filter-bar">
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('content.search_placeholder') }}" aria-label="{{ __('content.search_placeholder') }}">
        @foreach (['all', 'upcoming', 'no_next', 'unpublished'] as $f)
            <a class="chip" href="{{ route('admin.events', array_filter(['filter' => $f === 'all' ? null : $f, 'q' => $q ?: null])) }}" @if ($filter === $f) aria-current="page" @endif>{{ __('content.filter_'.$f) }}@if (isset($counts[$f])) {{ $counts[$f] }}@endif</a>
        @endforeach
        <x-button :href="route('admin.series.create')" variant="primary">{{ __('content.series_add') }}</x-button>
    </form>

    <h2 class="board-h2">{{ __('content.series_list') }}</h2>
    <div class="card board-table">
        <table class="table">
            <thead><tr><th>{{ __('content.col_series') }}</th><th>{{ __('content.col_next') }}</th><th>{{ __('content.col_events') }}</th><th>{{ __('content.col_status') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($series as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->title }}</strong><br>
                            <span class="t-small t-muted">{{ $item->region->name }}@if ($item->category) ・ {{ $item->category->name }}@endif ・ {{ $item->recurrence->label() }}</span>
                        </td>
                        <td><a class="plain-link" href="{{ route('admin.events', array_filter(['series' => $item->id, 'filter' => $filter === 'all' ? null : $filter])) }}">{{ isset($nextDates[$item->id]) ? \Illuminate\Support\Carbon::parse($nextDates[$item->id])->isoFormat('M/D(ddd)') : __('content.none') }}</a></td>
                        <td>{{ __('content.events_count', ['count' => $item->events_count]) }}</td>
                        <td>@php($noNext = $item->recurrence === \App\Enums\Recurrence::Yearly && ! isset($nextDates[$item->id]))@if ($noNext)<span class="status-tag is-warn">{{ __('content.tag_no_next') }}</span>@else<span class="status-tag is-ok">{{ __('content.tag_public') }}</span>@endif</td>
                        <td class="actions">
                            <a class="btn btn-sm" href="{{ route('admin.series.edit', $item) }}">{{ __('content.edit') }}</a>
                            <a class="btn btn-sm" href="{{ route('admin.events.create', ['series' => $item->id]) }}">{{ __('content.event_add') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="t-muted">{{ __('content.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $series->links() }}

    @if ($selected)
        <section class="card board-card" aria-labelledby="detail-title">
            <h2 class="t-h2" id="detail-title">{{ __('content.events_of', ['title' => $selected->title]) }}</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('content.col_date') }}</th><th>{{ __('content.col_time') }}</th><th>{{ __('content.col_note') }}</th><th>{{ __('content.col_status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($selected->events->sortByDesc(fn ($e) => $e->schedules->first()?->date?->timestamp ?? PHP_INT_MAX) as $event)
                            <tr class="row-strong"><td colspan="4">{{ $event->title }}@if (! $event->is_published) <span class="status-tag is-warn">{{ __('content.unpublished') }}</span>@endif <span class="status-tag {{ $event->displayStatus() === 'cancelled' ? 'is-cancelled' : ($event->displayStatus() === 'ended' ? 'is-ended' : 'is-ok') }}">{{ __('content.display_'.$event->displayStatus()) }}</span></td>
                                <td class="actions">
                                    <a class="btn btn-sm" href="{{ route('admin.events.edit', $event) }}">{{ __('content.edit') }}</a>
                                    <form method="post" action="{{ route('admin.events.copy', $event) }}" style="display:inline">@csrf<x-button type="submit" size="sm">{{ __('content.copy_next_year') }}</x-button></form>
                                </td>
                            </tr>
                            @foreach ($event->schedules as $schedule)
                                <tr>
                                    <td>{{ $schedule->date->isoFormat('YYYY/M/D(ddd)') }}</td>
                                    <td>{{ $schedule->start_time ? substr($schedule->start_time, 0, 5) : '' }}@if ($schedule->end_time)〜{{ substr($schedule->end_time, 0, 5) }}@endif</td>
                                    <td>{{ $schedule->note }}</td>
                                    <td><span class="status-tag {{ $schedule->is_cancelled ? 'is-cancelled' : ($schedule->date->toDateString() < $today ? 'is-ended' : 'is-ok') }}">{{ $schedule->is_cancelled ? __('content.display_cancelled') : ($schedule->date->toDateString() < $today ? __('content.display_ended') : __('content.display_scheduled')) }}</span></td>
                                    <td class="actions">
                                        @if ($schedule->is_cancelled)
                                            <form method="post" action="{{ route('admin.schedules.restore', $schedule) }}">@csrf<x-button type="submit" size="sm">{{ __('content.restore_day') }}</x-button></form>
                                        @elseif ($schedule->date->toDateString() >= $today)
                                            <form method="post" action="{{ route('admin.schedules.cancel', $schedule) }}">@csrf<x-button type="submit" size="sm">{{ __('content.cancel_day') }}</x-button></form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="t-caption t-muted">{{ __('content.cancel_note') }}</p>
        </section>
    @endif
</x-layouts.admin>