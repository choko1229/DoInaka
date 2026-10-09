@props(['items'])
<nav class="breadcrumbs" aria-label="{{ __('public.breadcrumbs') }}">
    <ol>
        @foreach ($items as $item)
            <li>
                @if ($item['url'] && ! $loop->last)
                    <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                @else
                    <span aria-current="page">{{ $item['name'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>