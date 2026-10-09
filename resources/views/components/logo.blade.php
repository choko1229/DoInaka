@props(['size' => 32, 'href' => '/'])
<a class="logo" href="{{ $href }}" style="font-size: {{ (int) $size }}px" aria-label="{{ config('app.name') }}">
    <svg class="logo-mark" viewBox="0 0 32 32" aria-hidden="true"><path d="M3 26 L12 13 L17 19 L21 14 L29 26" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/><path d="M24 5 V26 M20 8 H28 M21 11 H27" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><path d="M28 8 Q31 11 32 10" fill="none" stroke="currentColor" stroke-width="1.25"/></svg>
    <b>ド田舎</b><i>.net</i>
</a>
