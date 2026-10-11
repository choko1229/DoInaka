@php
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    $keep = collect(request()->query())->except(['page', 'month'])->all();
    $monthLink = fn (string $m): string => $basePath.'?'.http_build_query($keep + ['view' => 'calendar', 'month' => $m]);
@endphp
<div data-results>
    @include('public.events.partials.toolbar', ['total' => $calendar['total'], 'view' => 'calendar'])
    <div class="cal-head">
        <a class="chip" href="{{ $monthLink($calendar['prev']) }}" rel="prev">{{ __('public.cal_prev') }}</a>
        <h2 class="t-h2">{{ $calendar['month']->format('Y年n月') }}</h2>
        <a class="chip" href="{{ $monthLink($calendar['next']) }}" rel="next">{{ __('public.cal_next') }}</a>
    </div>
    <table class="cal" aria-label="{{ $calendar['month']->format('Y年n月') }}">
        <thead><tr>@foreach ($weekdays as $i => $w)<th scope="col" class="{{ $i === 0 ? 'is-sun' : ($i === 6 ? 'is-sat' : '') }}">{{ $w }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach ($calendar['weeks'] as $week)
                <tr>
                    @foreach ($week as $day)
                        <td class="{{ $day['inMonth'] ? '' : 'is-out' }} {{ $day['today'] ? 'is-today' : '' }}">
                            <span class="cal-day">{{ $day['date']->day }}</span>
                            @foreach ($day['events'] as $event)
                                <a class="cal-event" href="{{ app(\App\Services\Url\PublicLinks::class)->for($event) }}">{{ $event->title }}</a>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>