<x-layouts.public :meta="$meta" :pref="$pref" current="articles">
    <h1 class="t-h1">{{ $heading }}</h1>
    <form class="inline-form" method="get" action="{{ $basePath }}">
        <label class="visually-hidden" for="a-q">{{ __('public.keyword') }}</label>
        <input id="a-q" name="q" type="search" maxlength="100" value="{{ $query->q }}" placeholder="{{ __('public.search_placeholder') }}">
        <button class="btn btn-primary btn-sm" type="submit">{{ __('public.apply') }}</button>
    </form>
    <x-ad position="list" />
    @if ($articles->isEmpty())
        <x-empty-state :title="__('public.no_results')" :aside="__('public.no_results_aside')">{{ __('public.no_results_body') }}</x-empty-state>
    @else
        <div class="card-grid">@foreach ($articles as $article)<x-content-card :item="$article" />@endforeach</div>
        <x-pager :paginator="$articles" />
    @endif
</x-layouts.public>