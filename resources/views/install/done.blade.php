<x-layouts.install :step="4">
    <section class="card">
        <h2 class="t-h2">{{ __('install.done_title') }}</h2>
        <p>{{ __('install.done_body', ['name' => $name]) }}</p>
        <p class="t-small t-muted">{{ __('install.done_note') }}</p>
        <x-button variant="primary" :href="url('/')">{{ __('install.done_link') }}</x-button>
    </section>
</x-layouts.install>