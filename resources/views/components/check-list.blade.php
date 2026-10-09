@props(['results'])
<ul class="check-list">
    @foreach ($results as $result)
        <li class="check check-{{ $result->status->value }}">
            <span class="check-badge">{{ __('install.status_'.$result->status->value) }}</span>
            <span class="check-label">{{ $result->label }}</span>
            @if ($result->detail !== '')
                <span class="check-detail t-small t-muted">{{ $result->detail }}</span>
            @endif
        </li>
    @endforeach
</ul>