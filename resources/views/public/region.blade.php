@php
    $links = app(\App\Services\Url\PublicLinks::class);
    $resolver = app(\App\Services\Design\IllustUrlResolver::class);
    $illust = new \App\Data\Illust($isPref ? \App\Enums\Place::Island : \App\Enums\Place::Field, $themeContext->season, $themeContext->theme);
    $wide = $resolver->url($illust, \App\Enums\IllustVariant::Wide);
    $card = $resolver->url($illust, \App\Enums\IllustVariant::Card);
    $heroStyle = ($card ? '--hero-image: url('.$card.');' : '').($wide ? '--hero-image-wide: url('.$wide.');' : '');
    $stat = $region->stat;
    $merged = $former->filter(fn ($r) => $r->abolished_on !== null);
    $mergedOn = $merged->max('abolished_on');
    $units = $children->isNotEmpty() ? $children : $former;
    $mix = collect($events->items())->take(3)->concat(collect($spots->items())->take(3));
    $citations = collect(is_array($region->intro_sources) ? $region->intro_sources : [])->map(fn ($s, $i) => is_array($s) ? $s : ['url' => (string) $s, 'n' => $i + 1])->filter(fn ($s) => preg_match('#^https?://#i', (string) ($s['url'] ?? '')) === 1)->values();
@endphp
<x-layouts.public :meta="$meta" :pref="$pref" :compact="! $isPref">
    <x-slot:hero>
        <header class="hero-header region-hero" style="{{ $heroStyle }}">
            <div class="container">
                <x-hero-top :pref="$pref" />
            </div>
            <svg class="sky-header-hills" viewBox="0 0 1280 56" preserveAspectRatio="none" aria-hidden="true"><path d="M0 34 Q260 6 560 28 T1280 24 V56 H0Z"/></svg>
        </header>
    </x-slot:hero>

    <div class="detail-grid region-grid">
        <div class="detail-main">
            <header class="detail-head">
                <p class="detail-labels">
                    <span class="tag-chip">{{ $region->level->label() }}</span>
                    @if ($region->parent_id !== null && $region->parent)<span class="tag-chip">{{ $region->parent->name }}</span>@endif
                </p>
                <h1 class="detail-title">{{ $region->name }}@if ($region->name_kana) <small class="detail-kana">{{ $region->name_kana }}</small>@endif</h1>
                @if ($region->era)
                    <p class="alert">{{ $region->era->label() }}@if ($region->abolished_on)({{ __('public.abolished', ['date' => $region->abolished_on->format('Y年n月j日')]) }})@endif @if ($region->merged_into){{ __('public.merged_into', ['name' => $region->merged_into]) }}@endif</p>
                @endif
            </header>

            <section class="detail-section region-intro">
                @if ($region->intro_body)
                    <h2 class="detail-h2">{{ __('public.region_intro') }}</h2>
                    @foreach (preg_split("/\n{2,}/", $region->intro_body) ?: [] as $paragraph)<p>{!! nl2br(e($paragraph)) !!}</p>@endforeach
                    <p class="ai-note">{{ __('region.ai_notice') }}@if ($region->intro_generated_at) {{ __('region.last_checked', ['date' => $region->intro_generated_at->format('Y-m-d')]) }}@endif <a href="/report/region/{{ $region->id }}/">{{ __('public.report_difference') }}</a></p>
                @else
                    <p class="t-muted">{{ __('public.region_preparing') }}</p>
                @endif
            </section>

            @if ($units->isNotEmpty() || $alsoChildren->isNotEmpty())
                @php
                    $all = $units->concat($alsoChildren);
                    $groups = $isPref
                        ? [__('region.group_city') => $all->filter(fn ($r) => str_ends_with($r->name, '市')), __('region.group_town') => $all->reject(fn ($r) => str_ends_with($r->name, '市'))]
                        : ['' => $all];
                    $nCity = $all->filter(fn ($r) => str_ends_with($r->name, '市'))->count();
                    $nTown = $all->count() - $nCity;
                @endphp
                <section class="detail-section region-wide">
                    <div class="block-head">
                        <h2 class="detail-h2">{{ $isPref ? __('region.cities_title', ['name' => $region->name, 'city' => $nCity, 'town' => $nTown]) : __('region.areas_before', ['name' => $region->name]) }}</h2>
                        @if ($isPref)<span class="t-small t-muted">{{ __('region.former_from_city') }}</span>@endif
                    </div>
                    @foreach ($groups as $label => $list)
                        @continue($list->isEmpty())
                        @if ($label !== '')<h3 class="region-group">{{ $label }}</h3>@endif
                        <div class="region-cards">
                            @foreach ($list as $child)
                                <a class="region-card" href="{{ $links->region($child) }}">
                                    <strong>{{ $child->name }}</strong>
                                    @if ($child->name_kana)<span class="t-small t-muted">{{ $child->name_kana }}</span>@endif
                                    @if ($child->intro_body === null)<span class="t-small t-muted">{{ __('region.preparing_short') }}</span>@endif
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </section>
            @endif

            <section class="detail-section region-wide">
                <div class="block-head"><h2 class="detail-h2">{{ __('region.events_spots', ['name' => $region->name]) }}</h2><a href="/{{ $pref }}/events/">{{ __('public.see_all') }}</a></div>
                @if ($mix->isEmpty())<p class="t-muted">{{ __('public.no_events') }}</p>
                @else<div class="card-grid detail-more">@foreach ($mix as $item)<x-content-card :item="$item" />@endforeach</div>@endif
            </section>

            @if ($citations->isNotEmpty())
                <section class="detail-section region-wide">
                    <h2 class="detail-h2 small">{{ __('region.sources') }}</h2>
                    <ol class="region-sources">
                        @foreach ($citations as $c)
                            <li id="ref-{{ $c['n'] ?? $loop->iteration }}"><a href="{{ $c['url'] }}" rel="nofollow noopener" target="_blank">{{ $c['title'] ?? $c['url'] }}</a></li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>

        <aside class="detail-side">
            <section class="card side-card">
                <h2 class="detail-h2">{{ __('region.basic_info') }}</h2>
                <dl class="facts">
                    @if ($region->name_kana)<dt>{{ __('region.reading') }}</dt><dd>{{ $region->name_kana }}</dd>@endif
                    @if ($stat?->population !== null)<dt>{{ __('region.population') }}</dt><dd>{{ __('region.population_value', ['count' => number_format($stat->population), 'year' => $stat->population_year ?? '']) }}</dd>@endif
                    @if ($stat?->area_km2 !== null)<dt>{{ __('region.area') }}</dt><dd>{{ rtrim(rtrim((string) $stat->area_km2, '0'), '.') }} km²</dd>@endif
                    @if ($mergedOn)<dt>{{ __('region.merger') }}</dt><dd>{{ $mergedOn->format('Y年n月j日') }}<br><span class="t-small t-muted">{{ $merged->pluck('name')->prepend($region->name)->implode('・') }}</span></dd>@endif
                </dl>
                @if ($stat === null)<p class="t-small t-muted">{{ __('region.stats_pending') }}</p>
                @else<p class="t-small t-muted">{{ __('region.stats_auto') }}@if ($stat->source_label) {{ $stat->source_label }}@endif</p>@endif
            </section>
            @if ($shikoku->isNotEmpty())
                <section class="card side-card">
                    <h2 class="detail-h2">{{ __('region.other_prefs') }}</h2>
                    <p class="chip-row">@foreach ($shikoku as $other)<a class="chip" href="{{ $links->region($other) }}">{{ $other->name }}</a>@endforeach</p>
                    <p class="t-small t-muted">{{ __('region.other_prefs_note', ['name' => $region->name]) }}</p>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.public>
