@php($state = old('state', $article->exists ? ($article->is_published ? 'published' : 'draft') : 'draft'))
<x-layouts.admin :title="__('content.article_edit')" current="contents">
    <div class="page-head">
        <h1 class="t-h1">{{ $article->exists ? __('content.article_edit') : __('content.article_add') }}</h1>
        <p class="t-small t-muted">{{ __('content.revision_note') }}</p>
    </div>
    <p><a href="{{ route('admin.contents', ['tab' => 'article']) }}">{{ __('content.back_contents') }}</a></p>

    <form method="post" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}" class="grid-main-side">
        @csrf
        @if ($article->exists) @method('put') @endif
        <section class="card">
            <div class="field"><label for="title">{{ __('content.field_title') }}</label><input id="title" name="title" value="{{ old('title', $article->title) }}" required maxlength="200">@error('title')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="region_id">{{ __('content.field_region') }}</label><x-region-select :selected="old('region_id', $article->region_id)" />@error('region_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="body">{{ __('content.field_body') }}</label><textarea id="body" name="body" maxlength="50000">{{ old('body', $article->body) }}</textarea></div>
            <div class="field"><label for="tags">{{ __('content.field_tags') }}</label><input id="tags" name="tags" value="{{ old('tags', $tagsText) }}" maxlength="300"></div>
            <div class="field"><label for="relations">{{ __('content.field_relations') }}</label><textarea id="relations" name="relations" style="min-height:80px">{{ old('relations', $relationsText) }}</textarea><p class="t-caption t-muted">{{ __('content.field_relations_help') }}</p></div>
            <div class="field"><label for="slug">{{ __('content.field_slug') }}</label><input id="slug" name="slug" value="{{ old('slug', $article->slug) }}" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*"></div>
        </section>
        <aside class="card">
            <fieldset class="field"><legend>{{ __('content.field_state') }}</legend>
                <label><input type="radio" name="state" value="published" @checked($state === 'published')> {{ __('content.state_published') }}</label>
                <label><input type="radio" name="state" value="draft" @checked($state !== 'published')> {{ __('content.state_draft') }}</label></fieldset>
            <div class="field"><label for="reason">{{ __('content.field_reason') }}</label><input id="reason" name="reason" maxlength="200" value="{{ old('reason') }}"><p class="t-caption t-muted">{{ __('content.field_reason_help') }}</p></div>
            <x-button type="submit" variant="primary">{{ __('content.save') }}</x-button>
            @if ($article->exists)
                <p><a href="{{ route('admin.revisions', ['type' => 'article', 'id' => $article->id]) }}">{{ __('content.history_count', ['count' => $revisionCount]) }}</a></p>
            @endif
        </aside>
    </form>
    @if ($article->exists)
        <form method="post" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm(@js(__('content.delete_confirm')))">@csrf @method('delete')<x-button type="submit">{{ __('content.delete_mistake') }}</x-button></form>
    @endif
</x-layouts.admin>