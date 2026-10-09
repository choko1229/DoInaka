<x-layouts.admin :title="__('content.history')" current="contents">
    <div class="page-head">
        <h1 class="t-h1">{{ __('content.history') }}</h1>
        <p class="t-small t-muted">{{ $title }}</p>
    </div>
    <p><a href="{{ $back }}">{{ __('content.back_edit') }}</a></p>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('content.col_date') }}</th><th>{{ __('content.col_cause') }}</th><th>{{ __('content.col_actor') }}</th><th>{{ __('content.field_reason') }}</th><th>{{ __('content.col_diff') }}</th><th></th></tr></thead>
            <tbody>
                @foreach ($revisions as $revision)
                    @php
                        $changed = [];
                        $before = $revision->before['attributes'] ?? [];
                        foreach (($revision->after['attributes'] ?? []) as $key => $value) {
                            if (($before[$key] ?? null) != $value) { $changed[] = $key; }
                        }
                        foreach (['schedules', 'sources', 'tags', 'relations'] as $relation) {
                            if (($revision->before[$relation] ?? null) != ($revision->after[$relation] ?? null) && $revision->before !== null) { $changed[] = $relation; }
                        }
                    @endphp
                    <tr>
                        <td>{{ $revision->created_at?->setTimezone('Asia/Tokyo')->format('Y/n/j G:i') }}</td>
                        <td>{{ $revision->cause->label() }}</td>
                        <td>{{ $users[$revision->actor_user_id] ?? '—' }}</td>
                        <td>{{ $revision->reason }}</td>
                        <td class="t-small">{{ $revision->before === null ? '' : implode(', ', $changed) }}</td>
                        <td class="actions">
                            @if ($revision->before !== null)
                                <form method="post" action="{{ route('admin.revisions.rollback', $revision) }}" onsubmit="return confirm(@js(__('content.rollback_confirm')))">@csrf<x-button type="submit" size="sm">{{ __('content.rollback') }}</x-button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="t-caption t-muted">{{ __('content.rollback_note') }}</p>
</x-layouts.admin>