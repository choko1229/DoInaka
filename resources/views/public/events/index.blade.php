<x-layouts.public :meta="$meta" :pref="$pref" current="events">
    <h1 class="t-h1">{{ $heading }}</h1>
    <div class="list-layout">
        <aside class="filters">
            <form method="get" action="{{ $basePath }}" data-filter-form data-api="{{ url('/api/v1/events') }}" data-pref="{{ $pref }}">
                <div class="field">
                    <label for="f-q">{{ __('public.keyword') }}</label>
                    <input id="f-q" name="q" type="search" maxlength="100" value="{{ $query->q }}">
                </div>
                <div class="field">
                    <label for="f-when">{{ __('public.when') }}</label>
                    <select id="f-when" name="when">
                        <option value="">{{ __('public.when_any') }}</option>
                        <option value="today" @selected($query->preset === 'today')>{{ __('public.today') }}</option>
                        <option value="weekend" @selected($query->preset === 'weekend')>{{ __('public.weekend') }}</option>
                    </select>
                </div>
                <div class="field">
                    <label for="f-category">{{ __('public.category') }}</label>
                    <select id="f-category" name="category">
                        <option value="">{{ __('public.category_any') }}</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->slug }}" @selected($query->categoryId === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="f-from">{{ __('public.date_from') }}</label>
                    <input id="f-from" name="from" type="date" value="{{ $query->dateFrom }}">
                </div>
                <div class="field">
                    <label for="f-to">{{ __('public.date_to') }}</label>
                    <input id="f-to" name="to" type="date" value="{{ $query->dateTo }}">
                </div>
                <div class="field">
                    <label for="f-sort">{{ __('public.sort') }}</label>
                    <select id="f-sort" name="sort">
                        <option value="date" @selected($query->sort === 'date')>{{ __('public.sort_date') }}</option>
                        <option value="popular" @selected($query->sort === 'popular')>{{ __('public.sort_popular') }}</option>
                    </select>
                </div>
                <label class="check-row"><input type="checkbox" name="past" value="1" @checked($query->includePast)><span>{{ __('public.include_past') }}</span></label>
                <button class="btn btn-primary btn-block" type="submit">{{ __('public.apply') }}</button>
                <a class="t-small" href="{{ $basePath }}">{{ __('public.clear') }}</a>
            </form>
        </aside>
        <div class="results">
            @include('public.events.partials.results')
        </div>
    </div>
</x-layouts.public>