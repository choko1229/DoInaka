@props(['status'])
{{-- AI の状態のバッジ。色だけに頼らず、文字で状態を示す。処理中・待ち・延期は、自動更新の対象(data-ai-active) --}}
<span class="{{ $status->state->pillClass() }}" @if ($status->isActive()) data-ai-active @endif>{{ $status->state->label() }}</span>