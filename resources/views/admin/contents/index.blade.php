<x-layouts.admin :title="__('content.contents_title')" current="contents">
    <div class="page-head">
        <h1 class="t-h1">{{ __('content.contents_title') }}</h1>
        <p class="t-small t-muted">{{ __('content.contents_lead') }}</p>
    </div>

    <nav class="tabs" aria-label="{{ __('content.contents_title') }}">
        @foreach (['spot', 'article', 'comment'] as $t)
            <a href="{{ route('admin.contents', ['tab' => $t]) }}" @if ($tab === $t) aria-current="page" @endif>{{ __('content.tab_'.$t) }}</a>
        @endforeach
    </nav>

    <form method="get" action="{{ route('admin.contents') }}" class="inline-form">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('content.search') }}" aria-label="{{ __('content.search') }}">
        <x-button type="submit" variant="primary">{{ __('content.search') }}</x-button>
        @if ($tab === 'spot')<x-button :href="route('admin.spots.create')">{{ __('content.spot_add') }}</x-button>@endif
        @if ($tab === 'article')<x-button :href="route('admin.articles.create')">{{ __('content.article_add') }}</x-button>@endif
    </form>

    <div class="table-wrap">
        <table class="table">
            @if ($tab === 'comment')
                <thead><tr><th>{{ __('content.col_body') }}</th><th>{{ __('content.col_target') }}</th><th>{{ __('content.col_status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($items as $comment)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($comment->body, 80) }}</td>
                            <td class="t-small">{{ $comment->commentable_type }} #{{ $comment->commentable_id }}</td>
                            <td><span class="status-tag {{ $comment->status->value === 'hidden' ? 'is-cancelled' : 'is-ok' }}">{{ $comment->status->label() }}</span></td>
                            <td class="actions">
                                <form method="post" action="{{ route('admin.comments.moderate', $comment) }}">@csrf
                                    <input type="hidden" name="action" value="{{ $comment->status->value === 'hidden' ? 'show' : 'hide' }}">
                                    <x-button type="submit" size="sm">{{ $comment->status->value === 'hidden' ? __('content.comment_show') : __('content.comment_hide') }}</x-button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="t-muted">{{ __('content.empty') }}</td></tr>
                    @endforelse
                </tbody>
            @else
                <thead><tr><th>{{ __('content.col_title') }}</th><th>{{ __('content.field_region') }}</th><th>{{ __('content.col_status') }}</th><th>{{ __('content.col_updated') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><strong>{{ $item->title }}</strong>@if ($tab === 'spot' && $item->category)<br><span class="t-small t-muted">{{ $item->category->name }}</span>@endif</td>
                            <td>{{ $item->region->name }}</td>
                            <td><span class="status-tag {{ $item->is_published ? 'is-ok' : 'is-warn' }}">{{ $item->is_published ? __('content.state_published') : __('content.state_draft') }}</span></td>
                            <td class="t-small">{{ $item->updated_at?->setTimezone('Asia/Tokyo')->format('n/j G:i') }}</td>
                            <td class="actions"><a class="btn btn-sm" href="{{ route($tab === 'spot' ? 'admin.spots.edit' : 'admin.articles.edit', $item) }}">{{ __('content.edit') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="t-muted">{{ __('content.empty') }}</td></tr>
                    @endforelse
                </tbody>
            @endif
        </table>
    </div>
    {{ $items->links() }}
</x-layouts.admin>