<x-layouts.public :meta="$meta">
    <h1 class="t-h1">{{ __('mypage.nav_lists') }}</h1>
    <x-mypage-nav current="lists" />
    <p class="tabs">
        @foreach (['favorite' => 'favorite', 'want_to_go' => 'want_to_go', 'visited' => 'visited'] as $key => $label)
            <a class="chip" href="/mypage/lists/?list={{ $key }}" @if ($tab === $key) aria-current="page" @endif>{{ __('mypage.'.$label) }}</a>
        @endforeach
    </p>
    @if ($items === [])
        <x-empty-state :title="__('mypage.empty_list')" :aside="__('mypage.empty_list_aside')">{{ __('mypage.empty_list_body') }}</x-empty-state>
    @else
        <div class="card-grid">
            @foreach ($items as $item)
                <div>
                    <x-content-card :item="$item" />
                    @if ($tab !== 'visited')
                        <form method="post" action="{{ url('/api/v1/favorites/'.$item->getMorphClass().'/'.$item->getKey()) }}">
                            @csrf
                            <input type="hidden" name="list" value="{{ $tab }}">
                            <button class="link-button t-small" type="submit">{{ __('mypage.remove') }}</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.public>