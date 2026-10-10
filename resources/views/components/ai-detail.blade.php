@props(['status'])
{{-- AI の状態の詳しい説明: 一言の説明、次に試す時刻、失敗の理由(分かる言葉)、結果(判定・理由・作った文) --}}
@if ($status->state !== \App\Enums\AiState::None)
    <div class="ai-detail" @if ($status->isActive()) data-ai-active @endif>
        <p><x-ai-badge :status="$status" />@if ($status->note) <span class="t-small t-muted">{{ $status->note }}</span>@endif</p>
        @if ($status->nextTry)<p class="t-small">{{ __('aistatus.next_try_at', ['time' => $status->nextTry->setTimezone('Asia/Tokyo')->format('m/d H:i')]) }}</p>@endif
        @if ($status->error)<p class="alert alert-danger t-small" role="alert">{{ $status->error }}</p>@endif
        @if ($status->result !== [])
            <dl class="kv">
                @foreach ($status->result as [$label, $value])<dt>{{ $label }}</dt><dd style="white-space:pre-wrap">{{ $value }}</dd>@endforeach
            </dl>
        @endif
    </div>
@endif