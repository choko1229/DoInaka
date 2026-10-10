@php($meta = new \App\Support\PageMeta(title: __('inquiry.mypage_title'), noindex: true))
<x-layouts.public :meta="$meta">
    <h1 class="t-h1">{{ __('inquiry.mypage_title') }}</h1>
    <x-mypage-nav current="takedown" />
    <p>{{ __('inquiry.mypage_lead') }}</p>
    @if (session('status'))<p class="alert alert-success" role="status">{{ session('status') }}</p>@endif
    @forelse ($consents as $c)
        <section class="card">
            <p class="t-small t-muted">{{ $c->status->label() }} / {{ __('inquiry.mypage_deadline', ['date' => $c->deadline_at->format('Y-m-d')]) }}</p>
            <p><strong>{{ __('inquiry.mypage_right') }}:</strong> {{ $c->inquiry->right_type?->label() }}</p>
            <p><strong>{{ __('inquiry.mypage_body') }}</strong></p>
            <p style="white-space:pre-wrap">{{ $c->inquiry->body }}</p>
            @if ($c->inquiry->result === 'removed')
                <p>{{ __('inquiry.mypage_done_removed') }}</p>
            @elseif ($c->inquiry->result === 'kept')
                <p>{{ __('inquiry.mypage_done_kept') }}</p>
            @elseif ($c->status === \App\Enums\ConsentStatus::Pending)
                <form method="post" action="/mypage/takedown/{{ $c->id }}/">
                    @csrf
                    <label class="check-row"><input type="radio" name="answer" value="agree" required> <span>{{ __('inquiry.mypage_agree') }}</span></label>
                    <label class="check-row"><input type="radio" name="answer" value="object" required> <span>{{ __('inquiry.mypage_object') }}</span></label>
                    <div class="field"><label for="reason-{{ $c->id }}">{{ __('inquiry.mypage_reason') }}</label><textarea id="reason-{{ $c->id }}" name="reason" rows="3" maxlength="1000"></textarea></div>
                    <button class="btn btn-primary" type="submit">{{ __('inquiry.mypage_send') }}</button>
                </form>
            @endif
        </section>
    @empty
        <p class="t-muted">{{ __('inquiry.mypage_none') }}</p>
    @endforelse
</x-layouts.public>