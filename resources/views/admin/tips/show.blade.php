<x-layouts.admin :title="$submission->receipt_no" current="tips">
    <div class="page-head"><h1 class="t-h1">{{ __('submission.tab_tips') }} <code>{{ $submission->receipt_no }}</code></h1><a href="{{ route('admin.tips') }}">{{ __('submission.back_to_list') }}</a></div>
    @include('admin.tips.partials.detail')
</x-layouts.admin>
