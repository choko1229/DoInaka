{{-- ダッシュボードの「AI の状況」。処理中などがあるあいだ、10秒ごとに、この部分だけを自動で更新する(resources/js/ai-live.js) --}}
<section class="card" id="ai-status" data-ai-live{{ $ai['totals']['waiting'] + $ai['totals']['processing'] + $ai['totals']['deferred'] > 0 ? ' data-ai-active' : '' }}>
    <div class="card-head"><h2 class="t-h2">{{ __('aistatus.title') }}</h2>@can('manage-settings')<a href="{{ route('admin.settings', ['tab' => 'ai']) }}">{{ __('settings.tab.ai') }}</a>@endcan</div>
    @unless ($ai['configured'])<p class="alert alert-warning" role="status">{{ __('aistatus.disabled') }}</p>@endunless
    @if ($ai['paused_until'])<p class="alert alert-warning" role="status">{{ __('aistatus.paused', ['time' => $ai['paused_until']->setTimezone('Asia/Tokyo')->format('m/d H:i')]) }}</p>@endif

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('aistatus.col_kind') }}</th><th>{{ __('aistatus.col_waiting') }}</th><th>{{ __('aistatus.col_processing') }}</th><th>{{ __('aistatus.col_done') }}</th><th>{{ __('aistatus.col_failed') }}</th><th>{{ __('aistatus.col_deferred') }}</th></tr></thead>
            <tbody>
                @foreach ($ai['groups'] as $key => $g)
                    <tr><td>{{ __('aistatus.group.'.$key) }}</td><td>{{ $g['waiting'] }}</td><td>{{ $g['processing'] }}</td><td>{{ $g['done'] }}</td><td>@if ($g['failed'] > 0)<strong>{{ $g['failed'] }}</strong>@else{{ $g['failed'] }}@endif</td><td>{{ $g['deferred'] }}</td></tr>
                @endforeach
                <tr><th scope="row">{{ __('aistatus.total') }}</th><td>{{ $ai['totals']['waiting'] }}</td><td>{{ $ai['totals']['processing'] }}</td><td>{{ $ai['totals']['done'] }}</td><td>{{ $ai['totals']['failed'] }}</td><td>{{ $ai['totals']['deferred'] }}</td></tr>
            </tbody>
        </table>
    </div>

    <p>{{ $ai['limit'] > 0 ? __('aistatus.usage', ['count' => $ai['today'], 'limit' => $ai['limit']]) : __('aistatus.usage_nolimit', ['count' => $ai['today']]) }}</p>
    <p class="t-small t-muted">{{ __('aistatus.usage_help') }}</p>

    <h3 class="t-h3">{{ __('aistatus.last_error') }}</h3>
    @if ($ai['last_error'])
        <p class="t-small t-muted">{{ __('aistatus.last_error_line', ['at' => $ai['last_error']['at']->setTimezone('Asia/Tokyo')->format('m/d H:i'), 'purpose' => $ai['last_error']['purpose'], 'model' => $ai['last_error']['model'] ?: '—']) }}</p>
        <p class="alert alert-danger t-small" role="alert">{{ $ai['last_error']['message'] }}</p>
    @else
        <p class="t-small t-muted">{{ __('aistatus.last_error_none') }}</p>
    @endif

    <h3 class="t-h3">{{ __('aistatus.next_try') }}</h3>
    <p class="t-small">{{ $ai['next_try'] ? $ai['next_try']->setTimezone('Asia/Tokyo')->format('m/d H:i') : __('aistatus.next_try_none') }}</p>

    <h3 class="t-h3">{{ __('aistatus.models') }}</h3>
    <ul class="t-small">
        @foreach ($ai['models'] as $m)<li>{{ $m['purpose'] }}: {{ $m['models'] === [] ? __('aistatus.model_none') : implode(' → ', $m['models']) }}</li>@endforeach
    </ul>
    <p class="t-small t-muted">{{ __('aistatus.live') }}<span data-ai-updated></span></p>
</section>