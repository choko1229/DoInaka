@props(['overseas' => false])
{{-- 送信ボタンのすぐ近くに出す同意(民法548条の2・個人情報保護法28条。設計書13章) --}}
<div class="consent">
    <p class="t-small">{!! __('submission.consent_terms', [
        'terms' => '<a href="'.e(url('/terms/')).'" target="_blank" rel="noopener">'.e(__('public.terms')).'</a>',
        'privacy' => '<a href="'.e(url('/privacy/')).'" target="_blank" rel="noopener">'.e(__('public.privacy')).'</a>',
    ]) !!}</p>
    <label class="check-row">
        <input type="checkbox" name="consent_terms" value="1" required @checked(old('consent_terms'))>
        <span>{{ __('submission.consent_terms_label') }}</span>
    </label>
    @error('consent_terms')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    @if ($overseas)
        <label class="check-row">
            <input type="checkbox" name="consent_overseas" value="1" required @checked(old('consent_overseas'))>
            <span>{!! __('submission.consent_overseas_label', ['privacy' => '<a href="'.e(url('/privacy/')).'#overseas" target="_blank" rel="noopener">'.e(__('public.privacy')).'</a>']) !!}</span>
        </label>
        @error('consent_overseas')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    @endif
</div>