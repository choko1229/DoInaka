{{-- 管理者バーの部品。/admin/bar?url=… で返し、公開ページに差し込まれる(設計書6.5) --}}
<div class="admin-bar" role="region" aria-label="{{ __('public.admin_bar') }}">
    <a class="admin-bar-link" href="{{ url('/admin') }}">{{ __('layout.admin') }}</a>
    @if ($prelaunch)<span class="admin-bar-item" title="{{ __('prelaunch.badge_help') }}"><strong>{{ __('prelaunch.badge') }}</strong></span>@endif
    <span class="admin-bar-item">{{ __('public.bar_submissions') }} {{ $pendingSubmissions }}</span>
    <span class="admin-bar-item">{{ __('public.bar_corrections') }} {{ $pendingCorrections }}</span>
    <details class="admin-bar-menu">
        <summary>{{ __('public.bar_new') }}</summary>
        <a href="{{ route('admin.events.create') }}">{{ __('public.nav_events') }}</a>
        <a href="{{ route('admin.spots.create') }}">{{ __('public.nav_spots') }}</a>
        <a href="{{ route('admin.articles.create') }}">{{ __('public.nav_articles') }}</a>
    </details>
    <span class="admin-bar-item">{{ __('public.bar_ai_today', ['count' => $aiToday]) }}@if ($aiPausedUntil) · {{ __('public.bar_ai_paused', ['time' => $aiPausedUntil->setTimezone('Asia/Tokyo')->format('H:i')]) }}@endif</span>

    @if ($target)
        <details class="admin-bar-menu admin-bar-page">
            <summary>{{ __('public.bar_this_page') }}</summary>
            @if ($target instanceof \App\Models\Event)
                <a href="{{ route('admin.events.edit', $target) }}">{{ __('public.bar_edit') }}</a>
                <a href="{{ route('admin.events.edit', $target) }}#sources">{{ __('public.bar_reread_sources') }}</a>
                <a href="{{ route('admin.revisions', ['type' => 'event', 'id' => $target->id]) }}">{{ __('public.bar_history') }}</a>
                <form method="post" action="{{ route('admin.events.cancel', $target) }}" data-confirm="{{ __('public.bar_confirm_cancel') }}">@csrf<button type="submit">{{ __('public.bar_cancel_event') }}</button></form>
                <form method="post" action="{{ route('admin.bar.unpublish', ['type' => 'event', 'id' => $target->id]) }}" data-confirm="{{ __('public.bar_confirm_unpublish') }}">@csrf<input type="hidden" name="return" value="{{ $returnUrl }}"><button type="submit">{{ __('public.bar_unpublish') }}</button></form>
            @elseif ($target instanceof \App\Models\Spot)
                <a href="{{ route('admin.spots.edit', $target) }}">{{ __('public.bar_edit') }}</a>
                <a href="{{ route('admin.revisions', ['type' => 'spot', 'id' => $target->id]) }}">{{ __('public.bar_history') }}</a>
                <form method="post" action="{{ route('admin.bar.unpublish', ['type' => 'spot', 'id' => $target->id]) }}" data-confirm="{{ __('public.bar_confirm_unpublish') }}">@csrf<input type="hidden" name="return" value="{{ $returnUrl }}"><button type="submit">{{ __('public.bar_unpublish') }}</button></form>
            @elseif ($target instanceof \App\Models\Article)
                <a href="{{ route('admin.articles.edit', $target) }}">{{ __('public.bar_edit') }}</a>
                <a href="{{ route('admin.revisions', ['type' => 'article', 'id' => $target->id]) }}">{{ __('public.bar_history') }}</a>
                <form method="post" action="{{ route('admin.bar.unpublish', ['type' => 'article', 'id' => $target->id]) }}" data-confirm="{{ __('public.bar_confirm_unpublish') }}">@csrf<input type="hidden" name="return" value="{{ $returnUrl }}"><button type="submit">{{ __('public.bar_unpublish') }}</button></form>
            @elseif ($target instanceof \App\Models\Region)
                <form method="post" action="{{ route('admin.bar.regenerate', $target) }}">@csrf<input type="hidden" name="return" value="{{ $returnUrl }}"><button type="submit">{{ __('public.bar_regenerate_intro') }}</button></form>
                <a href="{{ route('admin.masters.regions.edit', $target) }}">{{ __('public.bar_edit') }}</a>
                <a href="{{ route('admin.revisions', ['type' => 'region', 'id' => $target->id]) }}">{{ __('public.bar_history') }}</a>
            @endif
        </details>
    @endif
    <form class="admin-bar-logout" method="post" action="{{ url('/logout') }}">@csrf<button type="submit">{{ $user?->name }} · {{ __('public.bar_logout') }}</button></form>
</div>