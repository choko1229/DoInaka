<x-layouts.admin :title="__('inquiry.admin_title')" current="inquiries">
    <div class="page-head">
        <h1 class="t-h1"><code>{{ $inquiry->receipt_no }}</code> @if ($inquiry->urgent)<span class="pill pill-failed">{{ __('inquiry.admin_urgent') }}</span>@endif</h1>
        <p class="t-small t-muted">{{ $inquiry->kind->label() }} / {{ $inquiry->status->label() }} / {{ $inquiry->created_at?->format('Y-m-d H:i') }}</p>
        <p><a href="{{ route('admin.inquiries') }}">← {{ __('inquiry.admin_title') }}</a></p>
    </div>
    @include('admin.inquiries.partials.detail')
</x-layouts.admin>
