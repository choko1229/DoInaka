@if ($page === 'about')
<x-layouts.public :meta="$meta">
    <article class="about">
        <h1 class="page-title">{{ $heading }}</h1>
        <div class="about-grid">
            <div class="about-main">
                <p class="about-tagline"><span class="t-aside">{{ __('layout.footer_aside') }}</span><small>{{ __('public.about_tagline_sub') }}</small></p>
                <div class="prose legal-body">{{ $body }}</div>
                <p class="chip-row">
                    <a class="chip" href="/terms/">{{ __('public.terms') }}</a>
                    <a class="chip" href="/privacy/">{{ __('public.privacy') }}</a>
                    <a class="chip" href="/contact/">{{ __('public.contact') }}</a>
                </p>
            </div>
            <aside class="about-side card side-card">
                <h2 class="detail-h2">{{ __('public.about') }}</h2>
                <dl class="facts about-facts">
                    @foreach ($facts as [$label, $value])
                        <dt>{{ $label }}</dt><dd>{{ $value }}</dd>
                    @endforeach
                </dl>
            </aside>
        </div>
    </article>
</x-layouts.public>
@else
<x-layouts.public :meta="$meta">
    <article class="legal container-narrow">
        <h1 class="t-h1">{{ $heading }}</h1>
        <div class="prose legal-body">{{ $body }}</div>
    </article>
</x-layouts.public>
@endif