@props(['current' => 'index'])
@php($pendingConsents = auth()->check() ? \App\Models\TakedownConsent::query()->where('user_id', auth()->id())->where('status', 'pending')->count() : 0)
<nav class="tabs" aria-label="{{ __('mypage.title') }}">
    <a href="/mypage/" @if ($current === 'index') aria-current="page" @endif>{{ __('mypage.nav_index') }}</a>
    <a href="/mypage/lists/" @if ($current === 'lists') aria-current="page" @endif>{{ __('mypage.nav_lists') }}</a>
    <a href="/mypage/submissions/" @if ($current === 'submissions') aria-current="page" @endif>{{ __('mypage.nav_submissions') }}</a>
    <a href="/mypage/profile/" @if ($current === 'profile') aria-current="page" @endif>{{ __('mypage.nav_profile') }}</a>
    <a href="/mypage/takedown/" @if ($current === 'takedown') aria-current="page" @endif>{{ __('inquiry.mypage_link') }}@if ($pendingConsents > 0) <span class="pill pill-failed">{{ $pendingConsents }}</span>@endif</a>
</nav>