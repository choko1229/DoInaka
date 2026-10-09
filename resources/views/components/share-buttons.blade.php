@props(['url', 'title'])
<div class="share">
    <span class="t-small t-muted">{{ __('public.share') }}</span>
    <a class="chip" rel="noopener" target="_blank" href="https://twitter.com/intent/tweet?{{ http_build_query(['url' => $url, 'text' => $title]) }}">X</a>
    <a class="chip" rel="noopener" target="_blank" href="https://social-plugins.line.me/lineit/share?{{ http_build_query(['url' => $url]) }}">LINE</a>
    <button class="chip" type="button" data-copy="{{ $url }}">{{ __('public.copy_url') }}</button>
    <details class="share-qr">
        <summary class="chip">{{ __('public.qr') }}</summary>
        <div class="qr" role="img" aria-label="{{ __('public.qr_label') }}">{!! \App\Support\QrCode::svg($url) !!}</div>
    </details>
</div>