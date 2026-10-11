<x-layouts.public :meta="$meta">
    <h1 class="t-h1">{{ __('mypage.posts_title') }}</h1>
    <x-mypage-nav current="posts" />
    <p class="t-small t-muted">{{ __('mypage.posts_lead') }}</p>
    @if (session('status'))<p class="alert alert-success" role="status">{{ session('status') }}</p>@endif

    @foreach (['spot' => $spots, 'article' => $articles] as $type => $items)
        @if ($items->isNotEmpty())
            <h2 class="t-h2">{{ __('enums.submission_type.'.$type) }}</h2>
            <ul class="own-posts">
                @foreach ($items as $post)
                    <li class="card side-card">
                        <div>
                            <a href="{{ $links->for($post) }}"><strong>{{ $post->title }}</strong></a>
                            @if (($pending[$type] ?? null) === $post->id)<span class="pill pill-warning">{{ __('mypage.edit_pending') }}</span>@endif
                        </div>
                        <div class="own-post-actions">
                            <a class="btn btn-sm" href="/mypage/posts/{{ $type }}/{{ $post->id }}/edit/">{{ __('mypage.edit') }}</a>
                            <form method="post" action="/mypage/posts/{{ $type }}/{{ $post->id }}/delete/" onsubmit="return confirm(@js(__('mypage.delete_confirm')))">
                                @csrf
                                <input type="hidden" name="confirm" value="1">
                                <button class="btn btn-sm" type="submit">{{ __('mypage.delete') }}</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    @endforeach
    @if ($spots->isEmpty() && $articles->isEmpty())<p class="t-muted">{{ __('mypage.posts_empty') }}</p>@endif
</x-layouts.public>