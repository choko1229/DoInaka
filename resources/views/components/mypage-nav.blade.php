@props(['current' => 'index'])
<nav class="tabs" aria-label="{{ __('mypage.title') }}">
    <a href="/mypage/" @if ($current === 'index') aria-current="page" @endif>{{ __('mypage.nav_index') }}</a>
    <a href="/mypage/lists/" @if ($current === 'lists') aria-current="page" @endif>{{ __('mypage.nav_lists') }}</a>
    <a href="/mypage/submissions/" @if ($current === 'submissions') aria-current="page" @endif>{{ __('mypage.nav_submissions') }}</a>
    <a href="/mypage/profile/" @if ($current === 'profile') aria-current="page" @endif>{{ __('mypage.nav_profile') }}</a>
</nav>