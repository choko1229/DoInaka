@php
    $pendingStates = [\App\Enums\SubmissionStatus::Received, \App\Enums\SubmissionStatus::Processing, \App\Enums\SubmissionStatus::AiPending, \App\Enums\SubmissionStatus::AiDeferred, \App\Enums\SubmissionStatus::InReview];
    $typeLabel = fn ($s) => $s->type === \App\Enums\SubmissionType::Tip ? __('mypage.type_event') : $s->type->label();
@endphp
<x-layouts.public :meta="$meta">
    @if ($user->status === \App\Enums\UserStatus::Suspended)
        <p class="alert alert-warning" role="status">{{ __('mypage.suspended_notice') }}</p>
    @endif
    @if (session('status'))<p class="alert alert-success" role="status">{{ session('status') }}</p>@endif
    <x-mypage-nav current="index" />
    <div class="mypage-grid">
        <aside class="mypage-side">
            <section class="card side-card">
                <div class="mypage-who">
                    <span class="avatar avatar-lg" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
                    <div>
                        <strong class="mypage-name">{{ $user->name }}</strong>
                        <a class="t-small" href="/users/{{ $user->id }}/">{{ __('mypage.public_profile') }}</a>
                    </div>
                </div>
                <p class="t-muted mypage-counts">{{ __('mypage.counts', ['posted' => $stats['posted'], 'published' => $stats['published'], 'visited' => $stats['visited']]) }}</p>
                @if ($stats['remaining'] > 0)
                    <p class="t-small">{!! __('mypage.remaining', ['count' => '<strong>'.$stats['remaining'].'件</strong>']) !!}</p>
                    <div class="meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $stats['ratio'] }}"><span style="width: {{ $stats['ratio'] }}%"></span></div>
                @else
                    <p class="t-small">{{ __('mypage.trusted') }}</p>
                @endif
            </section>
        </aside>

        <section class="mypage-main">
            <div class="mypage-head"><h1 class="detail-h2">{{ __('mypage.nav_submissions') }}</h1><span class="t-small t-muted">{{ __('mypage.review_time') }}</span></div>
            <div class="mypage-rows">
                @forelse ($submissions as $s)
                    @php
                        $pending = in_array($s->status, $pendingStates, true);
                        $rejected = $s->status->isRejected();
                        $target = null;
                        if ($s->status === \App\Enums\SubmissionStatus::Approved && in_array($s->target_type, ['event', 'spot', 'article'], true) && $s->target_id) {
                            $target = match ($s->target_type) { 'event' => \App\Models\Event::query()->find($s->target_id), 'spot' => \App\Models\Spot::query()->find($s->target_id), default => \App\Models\Article::query()->find($s->target_id) };
                            $target = $target?->is_published ? $target : null;
                        }
                        $title = $s->text('title') ?? $s->text('source_url') ?? $s->text('body') ?? $typeLabel($s);
                    @endphp
                    <article class="mypage-row card">
                        <div class="mypage-row-meta">
                            <span class="t-small t-muted">{{ $typeLabel($s) }}・{{ $s->created_at?->isoFormat('M月D日') }}</span>
                            <span @class(['status-tag', 'is-pending' => $pending, 'is-published' => $s->status === \App\Enums\SubmissionStatus::Approved, 'is-rejected' => $rejected])>{{ $pending ? __('mypage.status_pending') : ($rejected ? __('mypage.status_rejected') : __('mypage.status_published')) }}</span>
                        </div>
                        <h2 class="mypage-row-title">@if ($target)<a href="{{ $links->for($target) }}">{{ \Illuminate\Support\Str::limit($title, 60) }}</a>@else{{ \Illuminate\Support\Str::limit($title, 60) }}@endif</h2>
                        @if ($pending)<p class="t-muted">{{ __('mypage.pending_note') }}</p>@endif
                        @if ($rejected && $s->reject_reason)<p class="t-muted">{{ __('mypage.reason') }}: {{ $s->reject_reason }}</p>@endif
                        <p class="mypage-row-actions">
                            @if ($pending && in_array($s->status, [\App\Enums\SubmissionStatus::AiPending, \App\Enums\SubmissionStatus::AiDeferred, \App\Enums\SubmissionStatus::InReview], true))
                                <form method="post" action="/mypage/submissions/{{ $s->id }}/withdraw/">@csrf<button type="submit" class="link-button">{{ __('mypage.withdraw') }}</button></form>
                            @endif
                            @if ($target)
                                <a href="{{ $links->for($target) }}">{{ __('mypage.view') }}</a>
                                @if (in_array($s->target_type, ['spot', 'article'], true) && $target->author_user_id === $user->id)<a href="/mypage/posts/{{ $s->target_type }}/{{ $target->id }}/edit/">{{ __('mypage.edit_again') }}</a>@endif
                            @endif
                            @if ($rejected)<a href="/post/{{ $s->type->value }}/">{{ __('mypage.rewrite') }}</a>@endif
                        </p>
                    </article>
                @empty
                    <p class="t-muted">{{ __('mypage.empty_submissions') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.public>