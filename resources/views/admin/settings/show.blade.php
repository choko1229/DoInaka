@php
    $field = fn (\App\Enums\SettingKey $key): string => str_replace('.', '__', $key->value);
@endphp
<x-layouts.admin :title="__('settings.title')" current="settings">
    <div class="page-head"><h1 class="t-h1">{{ __('settings.title') }}</h1><p class="t-small t-muted">{{ __('settings.lead') }}</p></div>

    <div class="tabs">
        @foreach ($tabs as $t)
            <a href="{{ route('admin.settings', ['tab' => $t]) }}" @if ($tab === $t) aria-current="page" @endif>{{ __('settings.tab.'.$t) }}</a>
        @endforeach
        <a href="{{ route('admin.update') }}">{{ __('settings.tab.update') }}</a>
    </div>

    <form class="card" method="post" action="{{ route('admin.settings.update', ['tab' => $tab]) }}">
        @csrf
        @if ($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="t-small">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
        @endif

        @foreach ($keys as $key)
            @php($name = $field($key))
            {{-- 文言の表は 'site.name' のようにドットを含むキーなので、配列として引く(__('settings.keys.site.name.label') では引けない) --}}
            @php($entry = __('settings.keys')[$key->value] ?? [])
            @php($label = $entry['label'] ?? $key->value)
            @php($help = $entry['help'] ?? '')
            <div class="field">
                @if ($key->type() === \App\Enums\SettingType::Bool)
                    <label class="check-row"><input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" id="{{ $name }}" name="{{ $name }}" value="1" @checked(old($name, $settings->bool($key)))><span>{{ $label }}</span></label>
                @else
                    <label for="{{ $name }}">{{ $label }}</label>
                    @if ($key->isSecret())
                        @php($hint = $settings->secretHint($key))
                        <input id="{{ $name }}" name="{{ $name }}" type="password" autocomplete="new-password" placeholder="{{ $hint !== '' ? $hint : __('settings.secret_empty') }}">
                        <p class="t-small t-muted">{{ $hint !== '' ? __('settings.secret_set', ['hint' => $hint]) : __('settings.secret_unset') }}@if ($hint !== '') <label class="check-inline"><input type="checkbox" name="clear[{{ $name }}]" value="1"> {{ __('settings.secret_clear') }}</label>@endif</p>
                    @elseif ($key === \App\Enums\SettingKey::SearchDriver)
                        <select id="{{ $name }}" name="{{ $name }}"><option value="ngram" @selected($settings->string($key) === 'ngram')>ngram({{ __('settings.search_ngram') }})</option><option value="like" @selected($settings->string($key) === 'like')>LIKE({{ __('settings.search_like') }})</option></select>
                    @elseif ($key === \App\Enums\SettingKey::PopularityWeights)
                        @php($w = $settings->array($key))
                        <div class="repeat-row">
                            @foreach (['view' => 'weight_view', 'favorite' => 'weight_favorite', 'visited' => 'weight_visited'] as $item => $label)
                                <div class="field"><label for="w-{{ $item }}">{{ __('settings.'.$label) }}</label><input id="w-{{ $item }}" name="weights[{{ $item }}]" type="number" step="0.1" min="0" max="100" value="{{ old('weights.'.$item, $w[$item] ?? 0) }}"></div>
                            @endforeach
                        </div>
                    @elseif ($key->type() === \App\Enums\SettingType::Int || $key->type() === \App\Enums\SettingType::Float)
                        <input id="{{ $name }}" name="{{ $name }}" type="number" step="{{ $key->type() === \App\Enums\SettingType::Float ? '0.01' : '1' }}" value="{{ old($name, $settings->get($key)) }}">
                    @elseif ($key === \App\Enums\SettingKey::SiteDescription || $key === \App\Enums\SettingKey::SiteOperator)
                        <textarea id="{{ $name }}" name="{{ $name }}" rows="3">{{ old($name, $settings->string($key)) }}</textarea>
                    @else
                        <input id="{{ $name }}" name="{{ $name }}" type="text" value="{{ old($name, $settings->string($key)) }}" autocomplete="off">
                    @endif
                @endif
                @if ($help !== '')<p class="t-small t-muted">{{ $help }}</p>@endif
                @error($key->value)<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
        @endforeach

        @if ($tab === 'ai')
            <h2 class="t-h2">{{ __('settings.models_title') }}</h2>
            <p class="t-small t-muted">{{ __('settings.models_help') }}</p>
            @if ($freeModels === [])<p class="alert alert-warning" role="status">{{ __('settings.models_unknown') }}</p>@endif
            @foreach ($modelKeys as $purpose => $key)
                @php($current = array_values($settings->array($key)))
                <fieldset class="field">
                    <legend class="t-strong">{{ \App\Enums\AiPurpose::from($purpose)->label() }}</legend>
                    @foreach ([0 => 'models_first', 1 => 'models_backup'] as $i => $label)
                        <label class="t-small" for="m-{{ $purpose }}-{{ $i }}">{{ __('settings.'.$label) }}</label>
                        <select id="m-{{ $purpose }}-{{ $i }}" name="models[{{ $purpose }}][{{ $i }}]">
                            <option value="">—</option>
                            @foreach ($freeModels as $id => $modelName)<option value="{{ $id }}" @selected(($current[$i] ?? '') === $id)>{{ $modelName }}({{ $id }})</option>@endforeach
                            @if (isset($current[$i]) && ! isset($freeModels[$current[$i]]))<option value="{{ $current[$i] }}" selected>{{ $current[$i] }}({{ __('settings.models_not_in_list') }})</option>@endif
                        </select>
                    @endforeach
                    @error($key->value)<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </fieldset>
            @endforeach
        @endif

        <button class="btn btn-primary" type="submit">{{ __('content.save') }}</button>
    </form>
</x-layouts.admin>