<x-layouts.admin :title="__('crawl.title')" current="sources">
    <div class="page-head"><h1 class="t-h1">{{ $source->exists ? __('crawl.edit') : __('crawl.add') }}</h1><a href="{{ route('admin.sources') }}">{{ __('submission.back_to_list') }}</a></div>
    <form class="card container-narrow" method="post" action="{{ $source->exists ? route('admin.sources.update', $source) : route('admin.sources.store') }}">
        @csrf
        @if ($source->exists) @method('PUT') @endif
        @if ($candidate)<input type="hidden" name="candidate" value="{{ $candidate->id }}">@endif
        <div class="field"><label for="name">{{ __('crawl.col_name') }}</label><input id="name" name="name" value="{{ old('name', $source->name) }}" maxlength="200" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="url">URL</label><input id="url" name="url" type="url" value="{{ old('url', $source->url) }}" maxlength="500" required>@error('url')<p class="field-error">{{ $message }}</p>@enderror<p class="t-small t-muted">{{ __('crawl.url_help') }}</p></div>
        <div class="field">
            <label for="kind">{{ __('crawl.kind') }}</label>
            <select id="kind" name="kind"><option value="web" @selected(old('kind', $source->kind) === 'web')>{{ __('crawl.kind_web') }}</option><option value="rss" @selected(old('kind', $source->kind) === 'rss')>{{ __('crawl.kind_rss') }}</option></select>
        </div>
        <div class="field"><label for="region_id">{{ __('crawl.region') }}</label><x-region-select :selected="old('region_id', $source->region_id)" /><p class="t-small t-muted">{{ __('crawl.region_help') }}</p></div>
        <label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $source->is_active))><span>{{ __('crawl.active') }}</span></label>
        <button class="btn btn-primary" type="submit">{{ __('content.save') }}</button>
    </form>
    @if ($source->exists)
        <form method="post" action="{{ route('admin.sources.destroy', $source) }}" data-confirm="{{ __('crawl.delete_confirm') }}" style="margin-top:var(--space-4)">@csrf @method('DELETE')<button class="link-button" type="submit">{{ __('crawl.delete') }}</button></form>
    @endif
</x-layouts.admin>