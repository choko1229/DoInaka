@props(['warnings'])
@foreach ($warnings as $warning)
    <section class="alert alert-danger" role="alert">
        <strong>{{ $warning['title'] }}</strong>
        <p class="t-small" style="margin:var(--space-1) 0 0">{{ $warning['body'] }}</p>
    </section>
@endforeach