<x-layouts.admin :title="__('logs.title')" current="logs">
    <div class="page-head"><h1 class="t-h1">{{ __('logs.title') }}</h1><p class="t-small t-muted">{{ __('logs.errors_lead') }}</p></div>
    @include('admin.logs._tabs')
    <form method="get" action="{{ route('admin.logs', ['tab' => 'errors']) }}" class="inline-form">
        <select name="level" aria-label="{{ __('logs.level') }}"><option value="">{{ __('logs.all') }}</option>@foreach ($levels as $l)<option value="{{ $l }}" @selected($level === $l)>{{ $l }}</option>@endforeach</select>
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('logs.search') }}" aria-label="{{ __('logs.search') }}">
        <button class="btn btn-sm" type="submit">{{ __('public.apply') }}</button>
        <a class="btn btn-sm" href="{{ route('admin.logs.csv', array_merge(['tab' => 'errors'], request()->query())) }}">{{ __('logs.csv') }}</a>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('logs.at') }}</th><th>{{ __('logs.level') }}</th><th>{{ __('logs.message') }}</th></tr></thead>
            <tbody>
                @forelse ($entries as $e)
                    <tr>
                        <td>{{ $e['at'] }}</td>
                        <td><span class="pill {{ in_array($e['level'], ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true) ? 'pill-failed' : '' }}">{{ $e['level'] }}</span></td>
                        <td>{{ $e['message'] }}@if ($e['context'])<br><span class="t-small t-muted">{{ $e['context'] }}</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>