@php($siteKey = app(\App\Services\Setting\SettingsService::class)->string(\App\Enums\SettingKey::TurnstileSiteKey))
{{-- ハニーポット: 人には見えない欄。機械が埋めたら断る --}}
<div class="visually-hidden" aria-hidden="true">
    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label>
</div>
@if ($siteKey !== '')
    <div class="cf-turnstile" data-sitekey="{{ $siteKey }}"></div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
@foreach (['turnstile', 'rate_limit', 'urls', 'ng_word', 'website'] as $key)
    @error($key)<p class="alert alert-danger" role="alert">{{ $message }}</p>@enderror
@endforeach