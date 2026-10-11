<x-layouts.public :meta="$meta" :pref="$pref" current="events">
    <h1 class="page-title">{{ $heading }}</h1>
    <div class="list-layout">
        <aside class="filters">
            <form method="get" action="{{ $basePath }}" data-filter-form data-api="{{ url('/api/v1/events') }}" data-pref="{{ $pref }}" aria-label="{{ __('public.filter_aria') }}">
                @if ($view === 'calendar')<input type="hidden" name="view" value="calendar">@endif
                <div class="field">
                    <label for="f-q">{{ __('public.keyword') }}</label>
                    <input id="f-q" name="q" type="search" maxlength="100" value="{{ $query->q }}">
                </div>
                <div class="field">
                    <span class="field-label">{{ __('public.when') }}</span>
                    <div class="chip-radios">
                        @foreach (['today' => 'today', 'weekend' => 'weekend', 'month' => 'month', 'range' => 'range'] as $value => $label)
                            <label class="chip-radio"><input type="radio" name="when" value="{{ $value }}" @checked($when === $value)><span>{{ __('public.when_'.$label) }}</span></label>
                        @endforeach
                    </div>
                    <div class="range-fields">
                        <label for="f-from">{{ __('public.date_from') }}<input id="f-from" name="from" type="date" value="{{ $query->dateFrom }}"></label>
                        <label for="f-to">{{ __('public.date_to') }}<input id="f-to" name="to" type="date" value="{{ $query->dateTo }}"></label>
                    </div>
                </div>
                <div class="field">
                    <label for="f-area">{{ __('public.area') }}</label>
                    <select id="f-area" name="area">
                        <option value="">{{ __('public.area_all_pref', ['region' => $region->name]) }}</option>
                        @foreach ($areas as $a)<option value="{{ $a->slug }}" @selected($area === $a->slug)>{{ $a->name }}</option>@endforeach
                    </select>
                </div>
                <fieldset class="field category-field">
                    <legend>{{ __('public.category') }}</legend>
                    <div class="category-checks">
                        @foreach ($categories as $c)
                            <label class="check-row"><input type="checkbox" name="category[]" value="{{ $c->slug }}" @checked(in_array($c->id, $query->categoryIds, true) || $query->categoryId === $c->id)><span>{{ $c->name }}</span></label>
                        @endforeach
                    </div>
                    <select class="category-select" name="category[]" aria-label="{{ __('public.category') }}">
                        <option value="">{{ __('public.category_any') }}</option>
                        @foreach ($categories as $c)<option value="{{ $c->slug }}" @selected(in_array($c->id, $query->categoryIds, true))>{{ $c->name }}</option>@endforeach
                    </select>
                </fieldset>
                <label class="check-row"><input type="checkbox" name="near" value="1" data-near @checked($query->hasLocation())><span>{{ __('public.near_check') }}</span></label>
                <input type="hidden" name="lat" value="{{ $query->lat }}" data-near-lat>
                <input type="hidden" name="lng" value="{{ $query->lng }}" data-near-lng>
                <input type="hidden" name="r" value="{{ $query->radiusKm ? (int) $query->radiusKm : '' }}" data-near-r>
                <label class="check-row"><input type="checkbox" name="past" value="1" @checked($query->includePast)><span>{{ __('public.include_past') }}</span></label>
                <button class="btn btn-primary btn-block" type="submit">{{ __('public.apply_search') }}</button>
                <a class="t-small" href="{{ $basePath }}">{{ __('public.clear') }}</a>
            </form>
        </aside>
        <div class="results">
            <x-ad position="list" />
            @if ($view === 'calendar')
                @include('public.events.partials.calendar')
            @else
                @include('public.events.partials.results', ['basePath' => $basePath])
            @endif
        </div>
    </div>
</x-layouts.public>