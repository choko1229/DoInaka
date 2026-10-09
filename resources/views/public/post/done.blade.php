@php($meta = new \App\Support\PageMeta(title: __('submission.done_title'), noindex: true))
<x-layouts.public :meta="$meta" current="post">
    <section class="card container-narrow">
        <h1 class="t-h1">{{ __('submission.done_title') }}</h1>
        <p>{{ __('submission.done_lead') }}</p>
        <p class="t-small t-muted">{{ __('submission.receipt_no') }}</p>
        <p class="receipt"><code>{{ $receiptNo }}</code></p>
        <p class="t-small">{{ __('submission.done_note') }}</p>
        <p><a class="btn" href="/">{{ __('submission.back_to_top') }}</a></p>
    </section>
</x-layouts.public>