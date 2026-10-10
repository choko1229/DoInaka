@props(['position'])
@php
    $selector = app(\App\Services\Ads\AdSelector::class);
    $pr = $selector->pr($position);
    $adsenseClient = $pr === null ? $selector->adsense($position) : null;
@endphp
@if ($pr)
    {{-- PR 枠: 必ず「PR」と表示する --}}
    <aside class="ad ad-pr" aria-label="{{ __('ads.pr_label') }}">
        <span class="ad-label">{{ __('ads.pr') }}</span>
        @if ($pr->link_url && preg_match('#^https?://#i', $pr->link_url) === 1)
            <a href="{{ $pr->link_url }}" rel="sponsored nofollow noopener" target="_blank"><strong>{{ $pr->title }}</strong></a>
        @else
            <strong>{{ $pr->title }}</strong>
        @endif
        @if ($pr->body)<p class="t-small">{{ $pr->body }}</p>@endif
    </aside>
@elseif ($adsenseClient)
    {{-- AdSense(Google の広告だけ)。読み込みは同意のあとだけ(フェーズ8の同意バナーが data-consent で制御する) --}}
    <aside class="ad ad-adsense" aria-label="{{ __('ads.adsense_label') }}" data-consent-ads data-adsense-client="{{ $adsenseClient }}">
        <span class="ad-label">{{ __('ads.ad') }}</span>
        <ins class="adsbygoogle" style="display:block" data-ad-client="{{ $adsenseClient }}" data-ad-format="auto" data-full-width-responsive="true"></ins>
    </aside>
@endif