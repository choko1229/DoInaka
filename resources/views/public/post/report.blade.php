@php($meta = new \App\Support\PageMeta(title: __('submission.report_title'), noindex: true))
<x-layouts.public :meta="$meta">
    <h1 class="t-h1">{{ __('submission.report_title') }}</h1>
    <p>{{ __('submission.report_lead', ['title' => $target->title ?? $target->name]) }}</p>
    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="t-small">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    <form class="card container-narrow" method="post" action="/report/{{ $type }}/{{ $target->id }}/">
        @csrf
        <div class="field">
            <label for="field">{{ __('submission.report_field') }}</label>
            <select id="field" name="field" required>
                @foreach ($fields as $name)
                    <option value="{{ $name }}" @selected(old('field') === $name)>{{ __('submission.fields.'.$name) }}(いま: {{ \Illuminate\Support\Str::limit($current[$name] ?? '', 30) }})</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="proposed_value">{{ __('submission.report_value') }}</label>
            <textarea id="proposed_value" name="proposed_value" rows="4" maxlength="2000" required>{{ old('proposed_value') }}</textarea>
        </div>
        <div class="field">
            <label for="source_url">{{ __('submission.report_source') }}</label>
            <input id="source_url" type="url" name="source_url" maxlength="500" value="{{ old('source_url') }}" placeholder="https://">
            <p class="t-small t-muted">{{ __('submission.report_source_hint') }}</p>
        </div>
        <x-turnstile />
        <x-consent />
        <button class="btn btn-primary" type="submit">{{ __('submission.send') }}</button>
    </form>
</x-layouts.public>