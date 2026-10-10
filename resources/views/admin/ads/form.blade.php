<x-layouts.admin :title="__('ads.title')" current="ads">
    <div class="page-head"><h1 class="t-h1">{{ $slot->exists ? __('ads.edit_pr') : __('ads.add_pr') }}</h1><a href="{{ route('admin.ads') }}">{{ __('submission.back_to_list') }}</a></div>
    <form class="card container-narrow" method="post" action="{{ $slot->exists ? route('admin.ads.update', $slot) : route('admin.ads.store') }}">
        @csrf
        @if ($slot->exists) @method('PUT') @endif
        <div class="field"><label for="title">{{ __('ads.field_title') }}</label><input id="title" name="title" value="{{ old('title', $slot->title) }}" maxlength="100" required>@error('title')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="body">{{ __('ads.field_body') }}</label><textarea id="body" name="body" maxlength="300" rows="3">{{ old('body', $slot->body) }}</textarea></div>
        <div class="field"><label for="link_url">{{ __('ads.field_link') }}</label><input id="link_url" name="link_url" type="url" value="{{ old('link_url', $slot->link_url) }}" maxlength="500" required placeholder="https://">@error('link_url')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="position">{{ __('ads.field_position') }}</label><select id="position" name="position">@foreach ($positions as $p)<option value="{{ $p }}" @selected(old('position', $slot->position) === $p)>{{ __('ads.positions.'.$p) }}</option>@endforeach</select></div>
        <div class="repeat-row">
            <div class="field"><label for="starts_at">{{ __('ads.field_starts') }}</label><input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $slot->starts_at?->setTimezone('Asia/Tokyo')->format('Y-m-d\TH:i')) }}"></div>
            <div class="field"><label for="ends_at">{{ __('ads.field_ends') }}</label><input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $slot->ends_at?->setTimezone('Asia/Tokyo')->format('Y-m-d\TH:i')) }}">@error('ends_at')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
        </div>
        <label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $slot->is_active))><span>{{ __('ads.field_active') }}</span></label>
        <button class="btn btn-primary" type="submit">{{ __('content.save') }}</button>
    </form>
    @if ($slot->exists)
        <form method="post" action="{{ route('admin.ads.destroy', $slot) }}" data-confirm="{{ __('ads.delete_confirm') }}" style="margin-top:var(--space-4)">@csrf @method('DELETE')<button class="link-button" type="submit">{{ __('content.delete') }}</button></form>
    @endif
</x-layouts.admin>