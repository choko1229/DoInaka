<x-layouts.admin :title="__('ads.title')" current="ads">
    <div class="page-head"><h1 class="t-h1">{{ __('ads.title') }}</h1><p class="t-small t-muted">{{ __('ads.lead') }}</p></div>

    <section class="card">
        <h2 class="t-h2">{{ __('ads.adsense') }}</h2>
        <p class="t-small t-muted">{{ __('ads.adsense_help') }}</p>
        @unless ($adsenseReady)<p class="alert alert-warning" role="status">{{ __('ads.adsense_off') }}</p>@endunless
        <form method="post" action="{{ route('admin.ads.adsense') }}">
            @csrf
            @foreach ($positions as $position)
                <label class="check-row"><input type="checkbox" name="positions[]" value="{{ $position }}" @checked(! empty($adsense[$position]))><span>{{ __('ads.positions.'.$position) }}</span></label>
            @endforeach
            <button class="btn" type="submit">{{ __('ads.save_adsense') }}</button>
        </form>
    </section>

    <section class="card">
        <div class="card-head"><h2 class="t-h2">{{ __('ads.pr_title') }}</h2><x-button :href="route('admin.ads.create')" variant="primary" size="sm">{{ __('ads.add_pr') }}</x-button></div>
        <p class="t-small t-muted">{{ __('ads.pr_help') }}</p>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('ads.col_title') }}</th><th>{{ __('ads.col_position') }}</th><th>{{ __('ads.col_period') }}</th><th>{{ __('ads.col_state') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($prSlots as $slot)
                        @php
                            $state = ! $slot->is_active ? 'off' : ($slot->starts_at && $slot->starts_at->isFuture() ? 'before' : ($slot->ends_at && $slot->ends_at->isPast() ? 'after' : 'now'));
                        @endphp
                        <tr>
                            <td><strong>{{ $slot->title }}</strong></td>
                            <td>{{ __('ads.positions.'.$slot->position) }}</td>
                            <td>{{ $slot->starts_at?->setTimezone('Asia/Tokyo')->format('Y-m-d H:i') ?? '—' }} 〜 {{ $slot->ends_at?->setTimezone('Asia/Tokyo')->format('Y-m-d H:i') ?? '—' }}</td>
                            <td><span class="pill {{ $state === 'now' ? 'pill-success' : '' }}">{{ __('ads.state_'.$state) }}</span></td>
                            <td class="actions"><a class="btn btn-sm" href="{{ route('admin.ads.edit', $slot) }}">{{ __('content.edit') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="t-muted">{{ __('submission.review_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>