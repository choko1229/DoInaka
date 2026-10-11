@php
    $payload = $submission->payload ?? [];
    $ai = is_array($submission->ai_result) ? $submission->ai_result : [];
    $norm = is_array($ai['normalized'] ?? null) ? $ai['normalized'] : [];
    $labels = ['title' => __('submission.attributes.title'), 'address' => __('submission.attributes.address'), 'body' => __('submission.attributes.body'), 'hours' => __('submission.attributes.hours'), 'access' => __('submission.attributes.access')];
    $rows = [];
    foreach ($labels as $key => $label) {
        if (isset($payload[$key]) && is_string($payload[$key])) {
            $suggest = isset($norm[$key]) && is_string($norm[$key]) && trim($norm[$key]) !== '' ? trim($norm[$key]) : null;
            $rows[] = ['key' => $key, 'label' => $label, 'original' => $payload[$key], 'suggest' => $suggest, 'differs' => $suggest !== null && $suggest !== $payload[$key]];
        }
    }
    $categorySlug = $ai['category_slug'] ?? null;
    $categoryName = is_string($categorySlug) && $submission->type === \App\Enums\SubmissionType::Spot && ! isset($payload['category_id']) ? \App\Models\Category::query()->where('target', 'spot')->where('slug', $categorySlug)->value('name') : null;
    $romaji = is_string($ai['romaji_slug'] ?? null) ? $ai['romaji_slug'] : null;
    $title = $payload['title'] ?? $payload['source_url'] ?? $payload['proposed_value'] ?? $submission->receipt_no;
    $reasons = is_array($ai['reasons'] ?? null) ? $ai['reasons'] : [];
    $score = $submission->ai_score;
    $imageAi = is_array($ai['image'] ?? null) ? $ai['image'] : [];
    $queue = \App\Models\Submission::query()->where('status', \App\Enums\SubmissionStatus::InReview)->orderBy('id')->pluck('id')->values();
    $index = $queue->search($submission->id);
    $rejectReasons = __('submission.reject_reasons');
@endphp
<x-layouts.admin :title="$submission->receipt_no" current="review" :bare="true" :back-href="route('admin.review')" :back-label="__('submission.review_list')" :position="$index !== false ? __('submission.position', ['n' => $index + 1, 'total' => $queue->count()]) : null">
    <div class="review-wrap">
        <p class="t-small t-muted">{{ $submission->type->label() }}({{ $submission->action === \App\Enums\SubmissionAction::Update ? __('submission.action_update') : __('submission.action_create') }})・{{ $submission->user?->name ?? __('submission.anonymous') }}@if ($submission->user)({{ __('submission.approved_count', ['count' => $submission->user->approved_count]) }})@endif・{{ __('submission.col_receipt') }} {{ $submission->receipt_no }}・{{ $submission->created_at?->setTimezone('Asia/Tokyo')->isoFormat('M月D日 H:mm') }}@if ($submission->ip_hash)・{{ __('submission.ip_hash') }} {{ \Illuminate\Support\Str::limit($submission->ip_hash, 6, '…') }}@endif・{{ $submission->status->label() }}@if ($submission->reject_reason) — {{ $submission->reject_reason }}@endif</p>
        <h1 class="review-title">{{ \Illuminate\Support\Str::limit((string) $title, 80) }}</h1>

        <div class="review-grid">
            <div class="review-main">
                <section class="card review-card">
                    <h2>{{ __('submission.content_and_ai') }}</h2>
                    <form id="review-approve" method="post" action="{{ route('admin.review.approve', $submission) }}">@csrf<input type="hidden" name="adopt_shown" value="1"></form>
                    <table class="review-table">
                        <thead><tr><th>{{ __('submission.col_item') }}</th><th>{{ __('submission.as_posted') }}</th><th>{{ __('submission.ai_proposal') }}</th><th>{{ __('submission.adopt') }}</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <th scope="row">{{ $row['label'] }}</th>
                                    <td>{!! nl2br(e(\Illuminate\Support\Str::limit($row['original'], 400))) !!}</td>
                                    <td class="{{ $row['suggest'] !== null ? 'is-proposal' : '' }}">@if ($row['suggest'] !== null){!! nl2br(e(\Illuminate\Support\Str::limit($row['suggest'], 400))) !!}@else<span class="t-muted">—</span>@endif</td>
                                    <td>@if ($row['differs'] && $canApprove)<input type="checkbox" form="review-approve" name="adopt[]" value="{{ $row['key'] }}" checked aria-label="{{ __('submission.adopt') }}: {{ $row['label'] }}">@endif</td>
                                </tr>
                            @endforeach
                            @if ($categoryName !== null)
                                <tr><th scope="row">{{ __('submission.category') }}</th><td><span class="t-muted">{{ __('submission.unspecified') }}</span></td><td class="is-proposal">{{ $categoryName }}</td><td>@if ($canApprove)<input type="checkbox" form="review-approve" name="adopt[]" value="category" checked aria-label="{{ __('submission.adopt') }}: {{ __('submission.category') }}">@endif</td></tr>
                            @endif
                            @if ($romaji !== null)
                                <tr><th scope="row">{{ __('submission.url_romaji') }}</th><td><span class="t-muted">—</span></td><td class="is-proposal"><code>{{ $romaji }}</code></td><td>@if ($canApprove)<input type="checkbox" form="review-approve" name="adopt[]" value="slug" checked aria-label="{{ __('submission.adopt') }}: {{ __('submission.url_romaji') }}">@endif</td></tr>
                            @endif
                        </tbody>
                    </table>

                    @if ($correction)
                        <h3 class="t-h3">{{ __('submission.correction_diff') }}</h3>
                        <dl class="facts">
                            <dt>{{ __('submission.fields.'.$correction->field) }}({{ __('submission.now') }})</dt><dd>{{ $currentValue }}</dd>
                            <dt>{{ __('submission.proposed') }}</dt><dd>{{ $correction->proposed_value }}</dd>
                            @if ($correction->source_url)<dt>{{ __('submission.report_source') }}</dt><dd><a href="{{ $correction->source_url }}" rel="nofollow noopener" target="_blank">{{ $correction->source_url }}</a></dd>@endif
                        </dl>
                    @endif
                    @if (isset($payload['inspection']))
                        <p class="t-small">{{ __('submission.inspection', ['status' => __('submission.inspection_'.$payload['inspection']['status'])]) }}@if ($payload['inspection']['candidate']) {{ __('submission.inspection_candidate') }}@endif</p>
                    @endif
                </section>

                @if ($submission->media->isNotEmpty())
                    <section class="card review-card">
                        <h2>{{ __('submission.photos_count', ['count' => $submission->media->count()]) }}</h2>
                        <div class="review-photos">
                            @foreach ($submission->media as $media)
                                <figure>
                                    @if ($media->isProcessed())
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url((string) $media->path_small) }}" alt="" loading="lazy">
                                    @else
                                        <span class="review-photo-empty t-small">{{ __('submission.original_only') }}</span>
                                    @endif
                                    <figcaption class="t-small">
                                        @if (($imageAi['has_faces'] ?? false) === true)<span class="status-tag is-warn">{{ __('submission.has_faces') }}</span>@elseif ($imageAi !== [])<span class="status-tag is-ok">{{ __('submission.no_problem') }}</span>@endif
                                        <a href="{{ route('admin.media.original', $media) }}" target="_blank" rel="noopener">{{ __('submission.see_original') }}</a>
                                        <span class="t-muted">{{ $media->width }}×{{ $media->height }}@if ($media->credit) · {{ $media->credit }}@endif @if ($media->rights_agreed_at) · {{ __('submission.rights_ok') }}@endif</span>
                                    </figcaption>
                                </figure>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="review-side">
                @php($aiStatus = app(\App\Services\Ai\AiStatusService::class)->forSubmission($submission))
                <section class="card review-card">
                    <h2>{{ __('submission.ai_verdict') }}</h2>
                    @if ($score !== null)
                        <p class="board-line"><span>{{ __('submission.safety_score') }}</span><strong class="{{ $score >= 0.9 ? 'is-ok' : ($score < 0.5 ? 'is-bad' : '') }}">{{ number_format((float) $score, 2) }}</strong></p>
                        <p class="board-line"><span>{{ __('submission.spam') }}</span><strong>{{ ($ai['is_spam'] ?? false) ? __('submission.yes') : __('submission.no') }}</strong></p>
                        @if ($reasons !== [])<ul class="t-small">@foreach ($reasons as $reason)<li>{{ is_string($reason) ? $reason : '' }}</li>@endforeach</ul>@endif
                        @if (! empty($ai['would_reject']))<p class="t-small">{{ __('submission.ai_would_reject') }}</p>@endif
                    @else
                        <p class="t-muted">{{ __('submission.ai_none') }}</p>
                    @endif
                    @if ($aiStatus->state !== \App\Enums\AiState::None)
                        <div id="ai-live-detail" data-ai-live><x-ai-detail :status="$aiStatus" /></div>
                    @endif
                </section>

                @if (($ai['duplicate_of'] ?? null) !== null)
                    <section class="card review-card">
                        <h2>{{ __('submission.maybe_duplicate') }}</h2>
                        <p class="review-dup">#{{ $ai['duplicate_of'] }}<br><span class="t-small t-muted">{{ __('submission.duplicate_note') }}</span></p>
                    </section>
                @endif

                <section class="card review-card">
                    <h2>{{ __('submission.decision') }}</h2>
                    @if ($canApprove)
                        <button class="btn btn-primary btn-block" type="submit" form="review-approve">{{ __('submission.approve_checked') }}</button>
                        <hr>
                        <form method="post" action="{{ route('admin.review.reject', $submission) }}" class="review-reject">
                            @csrf
                            <label class="t-small t-muted" for="reject-reason">{{ __('submission.reject_reason_label') }}</label>
                            <select id="reject-reason" name="reason">
                                <option value="">{{ __('submission.choose_reason') }}</option>
                                @foreach ((array) $rejectReasons as $reasonText)<option value="{{ $reasonText }}">{{ $reasonText }}</option>@endforeach
                            </select>
                            <input type="text" name="note" maxlength="200" placeholder="{{ __('submission.note_optional') }}" aria-label="{{ __('submission.note_optional') }}">
                            <button class="btn btn-block btn-danger-outline" type="submit">{{ __('submission.reject') }}</button>
                        </form>
                        @if ($submission->user_id === null)<p class="t-small t-muted">{{ __('submission.anonymous_note') }}</p>@endif
                    @endif
                    @if ($submission->status->isRejected())
                        <form method="post" action="{{ route('admin.review.restore', $submission) }}">@csrf<button class="btn btn-block" type="submit">{{ __('submission.restore') }}</button></form>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</x-layouts.admin>