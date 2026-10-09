{{-- Cookie の同意バナー。最初は隠れていて、まだ選んでいないときだけ JavaScript が出す。選ぶまで、分析・広告のスクリプトは読み込まない --}}
<div class="cookie-banner" role="dialog" aria-labelledby="cookie-title" data-cookie-banner hidden>
    <p id="cookie-title"><strong>{{ __('cookie.title') }}</strong></p>
    <p class="t-small">{!! __('cookie.body', ['privacy' => '<a href="/privacy/#external">'.e(__('public.privacy')).'</a>']) !!}</p>
    <div class="cookie-actions">
        <button type="button" class="btn btn-primary" data-cookie-choice="granted">{{ __('cookie.accept') }}</button>
        <button type="button" class="btn" data-cookie-choice="denied">{{ __('cookie.deny') }}</button>
    </div>
</div>