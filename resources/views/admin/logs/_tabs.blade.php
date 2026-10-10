<div class="tabs">
    @foreach (['operations', 'reviews', 'ai', 'errors'] as $t)
        <a href="{{ route('admin.logs', ['tab' => $t]) }}" @if ($tab === $t) aria-current="page" @endif>{{ __('logs.tab_'.$t) }}</a>
    @endforeach
</div>