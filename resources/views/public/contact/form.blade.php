<x-layouts.public :meta="$meta">
    <h1 class="t-h1">{{ __('public.contact') }}</h1>
    <p>{{ __('inquiry.lead') }}</p>
    <p class="t-small t-muted">{{ __('inquiry.lead_hint') }}</p>
    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="t-small">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    <form class="card container-narrow" method="post" action="/contact/">
        @csrf
        <div class="field">
            <label for="kind">{{ __('inquiry.field_kind') }}</label>
            <select id="kind" name="kind" required>
                @foreach ($kinds as $k)
                    <option value="{{ $k->value }}" @selected(old('kind', $selected->value) === $k->value)>{{ $k->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="target_url">{{ __('inquiry.field_target_url') }}</label>
            <input id="target_url" type="url" name="target_url" maxlength="500" value="{{ old('target_url', $prefill['target_url']) }}" placeholder="https://">
            <p class="t-small t-muted">{{ __('inquiry.field_target_url_hint') }}</p>
        </div>
        @if ($prefill['media_id'] !== null)
            <input type="hidden" name="media_id" value="{{ old('media_id', $prefill['media_id']) }}">
            <p class="t-small">{{ __('inquiry.field_photo', ['id' => $prefill['media_id']]) }}</p>
        @endif
        <div class="field">
            <label for="right_type">{{ __('inquiry.field_right_type') }}</label>
            <select id="right_type" name="right_type">
                <option value="">{{ __('inquiry.select_placeholder') }}</option>
                @foreach ($rights as $r)
                    <option value="{{ $r->value }}" @selected(old('right_type') === $r->value)>{{ $r->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="organizer_name">{{ __('inquiry.field_organizer') }}</label>
            <input id="organizer_name" type="text" name="organizer_name" maxlength="200" value="{{ old('organizer_name') }}">
        </div>
        <div class="field">
            <label for="body">{{ __('inquiry.field_body') }}</label>
            <textarea id="body" name="body" rows="8" maxlength="3000" required>{{ old('body') }}</textarea>
        </div>
        <div class="field">
            <label for="email">{{ __('inquiry.field_email') }} <span class="t-small t-muted">{{ __('inquiry.field_email_optional') }}</span></label>
            <input id="email" type="email" name="email" maxlength="190" autocomplete="email" value="{{ old('email') }}">
            <p class="t-small t-muted">{{ __('inquiry.field_email_required') }}(@lang('enums.inquiry_kind.ads')・@lang('enums.inquiry_kind.privacy'))</p>
        </div>
        <p class="t-small takedown-notice">{{ __('inquiry.takedown_note', ['count' => app(\App\Services\Setting\SettingsService::class)->int(\App\Enums\SettingKey::TakedownDailyLimitPerIp)]) }}</p>
        <x-turnstile />
        @error('rate_limit')<p class="alert alert-danger" role="alert">{{ $message }}</p>@enderror
        <x-consent :overseas="true" />
        <button class="btn btn-primary" type="submit">{{ __('inquiry.send') }}</button>
    </form>
</x-layouts.public>