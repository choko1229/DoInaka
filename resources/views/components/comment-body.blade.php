@props(['comment', 'reply' => false])
@php
    $name = $comment->is_official ? config('app.name') : ($comment->user?->name ?? __('public.anonymous'));
@endphp
<div @class(['comment-item', 'is-reply' => $reply])>
    <span @class(['avatar', 'is-official' => $comment->is_official]) aria-hidden="true">{{ $comment->is_official ? __('public.official_initial') : mb_substr($name, 0, 1) }}</span>
    <div class="comment-text">
        <p class="comment-meta"><strong>{{ $name }}</strong>@if ($comment->is_official) <span class="pill">{{ __('public.official') }}</span>@endif <span class="t-muted">{{ $comment->created_at?->diffForHumans() }}</span></p>
        <p>{!! nl2br(e($comment->body)) !!}</p>
    </div>
</div>
