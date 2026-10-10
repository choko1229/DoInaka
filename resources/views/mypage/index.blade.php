<x-layouts.public :meta="$meta">
    <h1 class="t-display">{{ __('mypage.title') }}</h1>
    <p class="t-aside">{{ __('mypage.hello', ['name' => $user->name]) }}</p>
    @if ($user->status === \App\Enums\UserStatus::Suspended)
        <p class="alert alert-warning" role="status">{{ __('mypage.suspended_notice') }}</p>
    @endif
    <x-mypage-nav current="index" />
    <div class="card-grid">
        <a class="card" href="/mypage/lists/?list=favorite"><h2 class="t-h2">{{ __('mypage.favorite') }}</h2><p class="t-display">{{ $counts['favorite'] }}</p></a>
        <a class="card" href="/mypage/lists/?list=want_to_go"><h2 class="t-h2">{{ __('mypage.want_to_go') }}</h2><p class="t-display">{{ $counts['want_to_go'] }}</p></a>
        <a class="card" href="/mypage/lists/?list=visited"><h2 class="t-h2">{{ __('mypage.visited') }}</h2><p class="t-display">{{ $counts['visited'] }}</p></a>
        <a class="card" href="/mypage/submissions/"><h2 class="t-h2">{{ __('mypage.nav_submissions') }}</h2><p class="t-display">{{ $counts['submissions'] }}</p></a>
    </div>
    @if ($recent->isNotEmpty())
        <h2 class="t-h1" style="margin-top:var(--space-8)">{{ __('mypage.recent') }}</h2>
        <ul>@foreach ($recent as $s)<li>{{ $s->type->label() }} — <span class="pill">{{ $s->status->label() }}</span> <code>{{ $s->receipt_no }}</code></li>@endforeach</ul>
    @endif
</x-layouts.public>