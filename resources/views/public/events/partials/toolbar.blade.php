{{-- 件数・並び順と、表示の切り替え(一覧・カレンダー・地図) --}}
@php
    $keep = collect(request()->query())->except(['pref', 'page', 'view', 'month'])->all();
    $link = fn (array $extra): string => $basePath.($keep + $extra === [] ? '' : '?'.http_build_query($keep + $extra));
    $isCalendar = ($view ?? 'list') === 'calendar';
@endphp
<div class="results-bar">
    <span><b>{{ __('public.total_events', ['count' => $total]) }}</b> <span class="t-small t-muted">{{ ($query->sort ?? 'date') === 'popular' ? __('public.sort_popular') : __('public.sort_date') }}</span></span>
    <div class="view-toggle" role="group" aria-label="{{ __('public.view_aria') }}">
        <a href="{{ $link([]) }}" @if (! $isCalendar) aria-pressed="true" @endif>{{ __('public.view_list') }}</a>
        <a href="{{ $link(['view' => 'calendar']) }}" @if ($isCalendar) aria-pressed="true" @endif>{{ __('public.view_calendar') }}</a>
        <a href="/{{ $pref }}/map/">{{ __('public.nav_map') }}</a>
    </div>
</div>