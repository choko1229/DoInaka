@props(['type', 'id', 'favoriteCount' => 0, 'visitCount' => 0])
{{-- JS なしでも動くよう、普通のフォーム(POST + CSRF)。JS があれば fetch で更新する。未ログインはログイン画面へ --}}
<div class="reactions">
    <form method="post" action="{{ url("/api/v1/favorites/{$type}/{$id}") }}" data-reaction>
        @csrf
        <button class="btn btn-sm" type="submit">{{ __('public.favorite') }} <span data-count>{{ $favoriteCount }}</span></button>
    </form>
    <form method="post" action="{{ url("/api/v1/visits/{$type}/{$id}") }}" data-reaction>
        @csrf
        <button class="btn btn-sm" type="submit">{{ __('public.visited') }} <span data-count>{{ $visitCount }}</span></button>
    </form>
</div>