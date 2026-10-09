<div class="tabs">
    <a href="{{ route('admin.review') }}" @if (($current ?? '') === 'review') aria-current="page" @endif>{{ __('submission.tab_review') }} {{ $counts['review'] }}</a>
    <a href="{{ route('admin.review', ['tab' => 'waiting']) }}" @if (($current ?? '') === 'waiting') aria-current="page" @endif>{{ __('submission.tab_waiting') }} {{ $counts['waiting'] }}</a>
    <a href="{{ route('admin.review.rejected') }}" @if (($current ?? '') === 'rejected') aria-current="page" @endif>{{ __('submission.tab_rejected') }} {{ $counts['rejected'] }}</a>
    <a href="{{ route('admin.corrections') }}" @if (($current ?? '') === 'corrections') aria-current="page" @endif>{{ __('submission.tab_corrections') }}</a>
    <a href="{{ route('admin.tips') }}" @if (($current ?? '') === 'tips') aria-current="page" @endif>{{ __('submission.tab_tips') }}</a>
</div>