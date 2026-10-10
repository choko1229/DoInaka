<x-layouts.public :meta="$meta" :pref="$pref" current="spots">
    <h1 class="t-h1">{{ $heading }}</h1>
    <form class="inline-form" method="get" action="{{ $basePath }}">
        <label class="visually-hidden" for="s-q">{{ __('public.keyword') }}</label>
        <input id="s-q" name="q" type="search" maxlength="100" value="{{ $query->q }}" placeholder="{{ __('public.search_placeholder') }}">
        <select name="category" aria-label="{{ __('public.category') }}">
            <option value="">{{ __('public.category_any') }}</option>
            @foreach ($categories as $c)<option value="{{ $c->slug }}" @selected($query->categoryId === $c->id)>{{ $c->name }}</option>@endforeach
        </select>
        <select name="sort" aria-label="{{ __('public.sort') }}">
            <option value="date" @selected($query->sort === 'date')>{{ __('public.sort_new') }}</option>
            <option value="popular" @selected($query->sort === 'popular')>{{ __('public.sort_popular') }}</option>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">{{ __('public.apply') }}</button>
    </form>
    <x-ad position="list" />
    @if ($spots->isEmpty())
        <x-empty-state :title="__('public.no_results')" :aside="__('public.no_results_aside')">{{ __('public.no_results_body') }}</x-empty-state>
    @else
        <div class="card-grid">@foreach ($spots as $spot)<x-content-card :item="$spot" />@endforeach</div>
        <x-pager :paginator="$spots" />
    @endif
</x-layouts.public>