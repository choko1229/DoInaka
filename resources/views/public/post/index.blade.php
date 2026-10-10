@php
    $meta = new \App\Support\PageMeta(title: __('submission.post_title'), description: __('submission.post_description'), noindex: true);
@endphp
<x-layouts.public :meta="$meta" current="post">
    <h1 class="t-display">{{ __('submission.post_title') }}</h1>
    <p>{{ __('submission.post_lead') }}</p>
    <div class="card-grid post-choices">
        <a class="card" href="/post/tip/"><h2 class="t-h2">{{ __('enums.submission_type.tip') }}</h2><p class="t-small">{{ __('submission.choice_tip') }}</p></a>
        <a class="card" href="/post/spot/"><h2 class="t-h2">{{ __('enums.submission_type.spot') }}</h2><p class="t-small">{{ __('submission.choice_spot') }}</p></a>
        <a class="card" href="/post/article/"><h2 class="t-h2">{{ __('enums.submission_type.article') }}</h2><p class="t-small">{{ __('submission.choice_article') }}</p></a>
    </div>
    <p class="t-small t-muted">{{ __('submission.post_note') }}</p>
</x-layouts.public>