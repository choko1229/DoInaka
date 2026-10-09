@php
    $meta = new \App\Support\PageMeta(title: __('submission.photo_title'), noindex: true);
    $maxPhotos = 5;
@endphp
<x-layouts.public :meta="$meta">
    <h1 class="t-h1">{{ __('submission.photo_title') }}</h1>
    <p>{{ __('submission.photo_lead', ['title' => $target->title]) }}</p>
    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="t-small">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    <form class="card container-narrow" method="post" action="/post/photo/{{ $type }}/{{ $target->id }}/" enctype="multipart/form-data">
        @csrf
        @include('public.post.partials.photos', ['label' => __('submission.photos')])
        <div class="field"><label for="credit">{{ __('submission.credit') }}</label><input id="credit" type="text" name="credit" maxlength="100" value="{{ old('credit') }}"><p class="t-small t-muted">{{ __('submission.credit_hint') }}</p></div>
        <x-turnstile />
        <x-consent :overseas="true" />
        <button class="btn btn-primary" type="submit">{{ __('submission.send') }}</button>
    </form>
</x-layouts.public>