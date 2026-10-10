<x-layouts.public :meta="$meta">
    <section class="card container-narrow">
        <h1 class="t-h1">{{ __('inquiry.done_title') }}</h1>
        <p>{{ __('inquiry.done_lead') }}</p>
        <p class="t-small t-muted">{{ __('inquiry.done_receipt') }}</p>
        <p class="receipt"><code>{{ $receiptNo }}</code></p>
        @if ($kind === \App\Enums\InquiryKind::Takedown)
            <p class="t-small">{{ __('inquiry.done_takedown') }}</p>
        @endif
        <p class="t-small">{{ __('inquiry.done_email') }}</p>
        <p><a class="btn" href="/">{{ __('inquiry.back_home') }}</a></p>
    </section>
</x-layouts.public>