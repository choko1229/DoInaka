@props(['type', 'id', 'favoriteCount' => 0, 'visitCount' => 0, 'primary' => null])
{{-- JS なしでも動くよう、普通のフォーム(POST + CSRF)。JS があれば fetch で更新する。未ログインはログイン画面へ --}}
<div @class(['reactions', 'is-visited-primary' => $primary === 'visited'])>
    <form method="post" action="{{ url("/api/v1/visits/{$type}/{$id}") }}" data-reaction>
        @csrf
        <button class="btn btn-sm reaction-visited" type="submit">{{ __('public.visited') }} <span data-count>{{ $visitCount }}</span></button>
    </form>
    <form class="reaction-want" method="post" action="{{ url("/api/v1/favorites/{$type}/{$id}") }}" data-reaction>
        @csrf
        <input type="hidden" name="list" value="want_to_go">
        <button class="btn" type="submit">{{ __('public.want_to_go') }}</button>
    </form>
    <form method="post" action="{{ url("/api/v1/favorites/{$type}/{$id}") }}" data-reaction>
        @csrf
        <button class="btn btn-sm" type="submit">{{ __('public.favorite') }} <span data-count>{{ $favoriteCount }}</span></button>
    </form>
</div>
