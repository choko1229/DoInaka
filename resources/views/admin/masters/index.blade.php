<x-layouts.admin :title="__('masters.title')" current="masters">
    <div class="page-head">
        <h1 class="t-h1">{{ __('masters.title') }}</h1>
        <p class="t-small t-muted">{{ __('masters.lead') }}</p>
    </div>

    <nav class="tabs" aria-label="{{ __('masters.title') }}">
        @foreach (['regions', 'categories', 'tags', 'ng'] as $t)
            <a href="{{ route('admin.masters', ['tab' => $t]) }}" @if ($tab === $t) aria-current="page" @endif>{{ __('masters.tab_'.$t) }}</a>
        @endforeach
    </nav>

    @if ($tab === 'regions')
        <form method="get" action="{{ route('admin.masters') }}" class="inline-form">
            <input type="hidden" name="tab" value="regions">
            <label for="pref" class="visually-hidden">{{ __('masters.pref_label') }}</label>
            <select id="pref" name="pref" data-autosubmit>
                @foreach ($prefectures as $pref)
                    <option value="{{ $pref->slug }}" @selected($selected?->id === $pref->id)>{{ $pref->name }}</option>
                @endforeach
            </select>
            <noscript><x-button type="submit">{{ __('masters.pref_label') }}</x-button></noscript>
        </form>

        @if ($selected)
            <section class="card">
                <h2 class="t-h2">{{ $selected->name }}</h2>
                <form method="post" action="{{ route('admin.masters.regions.flags', $selected) }}" class="flags">
                    @csrf
                    <label class="check-row"><input type="checkbox" name="accepts_posts" value="1" @checked($selected->accepts_posts)><span>{{ __('masters.col_accepts') }}</span></label>
                    <label class="check-row"><input type="checkbox" name="crawl_enabled" value="1" @checked($selected->crawl_enabled)><span>{{ __('masters.col_crawl') }}</span></label>
                    <x-button type="submit" size="sm">{{ __('masters.save') }}</x-button>
                </form>
                <p class="t-caption t-muted">{{ __('masters.pref_flags_note') }}</p>
            </section>

            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('masters.col_name') }}</th><th>{{ __('masters.col_url') }}</th><th>{{ __('masters.col_public') }}</th><th>{{ __('masters.col_count') }}</th><th></th></tr></thead>
                    <tbody>
                        <tr class="row-strong"><td>{{ $selected->name }}</td><td>/{{ $selected->slug }}/</td><td>{{ $selected->is_active ? '○' : '—' }}</td><td>{{ $usage[$selected->id] ?? 0 }}</td><td><a href="{{ route('admin.masters.regions.edit', $selected) }}">{{ __('masters.edit') }}</a></td></tr>
                        @foreach ($cities as $city)
                            <tr>
                                <td class="indent-1">{{ $city->name }}</td>
                                <td>/{{ $selected->slug }}/{{ $city->slug }}/</td>
                                <td>{{ $city->is_active ? '○' : '—' }}</td>
                                <td>{{ $usage[$city->id] ?? 0 }}</td>
                                <td><a href="{{ route('admin.masters.regions.edit', $city) }}">{{ __('masters.edit') }}</a></td>
                            </tr>
                            @foreach ($olds[$city->id] ?? [] as $old)
                                <tr class="row-old">
                                    <td class="indent-2">{{ $old->name }} <span class="pill">{{ $old->era?->label() }}</span></td>
                                    <td>/{{ $selected->slug }}/{{ $city->slug }}/{{ $old->slug }}/</td>
                                    <td>{{ $old->is_active ? '○' : '—' }}</td>
                                    <td>{{ $usage[$old->id] ?? 0 }}</td>
                                    <td><a href="{{ route('admin.masters.regions.edit', $old) }}">{{ __('masters.edit') }}</a></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @elseif ($tab === 'categories')
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('masters.category_target') }}</th><th>{{ __('masters.col_name') }}</th><th>{{ __('masters.category_slug') }}</th><th>{{ __('masters.category_order') }}</th><th>{{ __('masters.category_active') }}</th><th></th></tr></thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <form method="post" action="{{ route('admin.masters.categories.update', $category) }}" id="cat-{{ $category->id }}">@csrf</form>
                            <td>{{ $category->target->label() }}</td>
                            <td><input form="cat-{{ $category->id }}" name="name" value="{{ $category->name }}" required maxlength="60"></td>
                            <td>{{ $category->slug }}</td>
                            <td><input form="cat-{{ $category->id }}" name="sort_order" type="number" min="0" max="9999" value="{{ $category->sort_order }}" style="width:5rem"></td>
                            <td><input form="cat-{{ $category->id }}" type="checkbox" name="is_active" value="1" @checked($category->is_active)></td>
                            <td class="actions">
                                <x-button type="submit" size="sm" form="cat-{{ $category->id }}">{{ __('masters.save') }}</x-button>
                                <form method="post" action="{{ route('admin.masters.categories.destroy', $category) }}" style="display:inline">@csrf @method('delete')<x-button type="submit" size="sm">{{ __('masters.delete') }}</x-button></form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <section class="card">
            <h2 class="t-h2">{{ __('masters.category_new') }}</h2>
            <form method="post" action="{{ route('admin.masters.categories.store') }}" class="inline-form">
                @csrf
                <select name="target" required>@foreach (\App\Enums\CategoryTarget::cases() as $target)<option value="{{ $target->value }}">{{ $target->label() }}</option>@endforeach</select>
                <input name="name" placeholder="{{ __('masters.col_name') }}" required maxlength="60">
                <input name="slug" placeholder="{{ __('masters.category_slug') }}" required maxlength="60" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                <x-button type="submit" variant="primary">{{ __('masters.add') }}</x-button>
            </form>
            @error('slug')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </section>
    @elseif ($tab === 'tags')
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('masters.col_name') }}</th><th>{{ __('masters.tag_uses') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($tags as $tag)
                        <tr>
                            <td>{{ $tag->name }}</td>
                            <td>{{ $tagUsage[$tag->id] ?? 0 }}</td>
                            <td><form method="post" action="{{ route('admin.masters.tags.destroy', $tag) }}">@csrf @method('delete')<x-button type="submit" size="sm">{{ __('masters.delete') }}</x-button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="t-muted">{{ __('masters.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <section class="card">
            <h2 class="t-h2">{{ __('masters.tag_new') }}</h2>
            <form method="post" action="{{ route('admin.masters.tags.store') }}" class="inline-form">
                @csrf
                <input name="name" required maxlength="60" placeholder="{{ __('masters.col_name') }}">
                <x-button type="submit" variant="primary">{{ __('masters.add') }}</x-button>
            </form>
        </section>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('masters.ng_word') }}</th><th>{{ __('masters.ng_match') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($ngWords as $word)
                        <tr>
                            <td>{{ $word->word }}</td>
                            <td>{{ $word->match_type->value }}</td>
                            <td><form method="post" action="{{ route('admin.masters.ng.destroy', $word) }}">@csrf @method('delete')<x-button type="submit" size="sm">{{ __('masters.delete') }}</x-button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="t-muted">{{ __('masters.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <section class="card">
            <h2 class="t-h2">{{ __('masters.ng_new') }}</h2>
            <form method="post" action="{{ route('admin.masters.ng.store') }}" class="inline-form">
                @csrf
                <input name="word" required maxlength="100" placeholder="{{ __('masters.ng_word') }}">
                <select name="match_type">@foreach (\App\Enums\MatchType::cases() as $type)<option value="{{ $type->value }}">{{ $type->value }}</option>@endforeach</select>
                <x-button type="submit" variant="primary">{{ __('masters.add') }}</x-button>
            </form>
        </section>
    @endif
</x-layouts.admin>