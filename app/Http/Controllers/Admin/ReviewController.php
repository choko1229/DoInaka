<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Exceptions\InvalidSubmissionTransition;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Revision;
use App\Models\Submission;
use App\Models\User;
use App\Services\Ai\SuggestionApplier;
use App\Services\Content\RevisionService;
use App\Services\Submission\CorrectionFields;
use App\Services\Submission\ReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 審査(設計書6.2): 審査待ちの一覧と詳細、承認・却下・元に戻す、却下ボックス、修正依頼、元画像の確認。
 * 状態を変えるのは ReviewService(→ SubmissionStateMachine)だけ。
 */
final class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $review) {}

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'waiting' ? 'waiting' : 'review';
        $type = SubmissionType::tryFrom($request->string('type')->toString());

        $statuses = $tab === 'review'
            ? [SubmissionStatus::InReview]
            : [SubmissionStatus::Received, SubmissionStatus::Processing, SubmissionStatus::AiPending, SubmissionStatus::AiDeferred];

        $query = Submission::query()->whereIn('status', $statuses)->with('user')->withCount('media');
        if ($type !== null) {
            $query->where('type', $type);
        }

        return view('admin.review.index', [
            'submissions' => $query->orderBy('id')->paginate(20)->withQueryString(),
            'tab' => $tab,
            'type' => $type,
            'counts' => [
                'review' => Submission::query()->where('status', SubmissionStatus::InReview)->count(),
                'waiting' => Submission::query()->whereIn('status', [SubmissionStatus::Received, SubmissionStatus::Processing, SubmissionStatus::AiPending, SubmissionStatus::AiDeferred])->count(),
                'rejected' => Submission::query()->whereIn('status', [SubmissionStatus::Rejected, SubmissionStatus::AutoRejected])->count(),
            ],
        ]);
    }

    public function show(Submission $submission): View
    {
        $submission->load(['user', 'media.original', 'corrections', 'reviewer']);
        $correction = $submission->corrections->first();

        return view('admin.review.show', [
            'submission' => $submission,
            'correction' => $correction,
            'currentValue' => $correction === null ? null : $this->currentValue($submission),
            'canApprove' => $submission->status === SubmissionStatus::InReview || in_array($submission->status, [SubmissionStatus::AiPending, SubmissionStatus::AiDeferred], true),
        ]);
    }

    public function approve(Request $request, Submission $submission): RedirectResponse
    {
        $request->validate(['adopt' => ['nullable', 'array'], 'adopt.*' => ['string', 'in:title,body,address,hours,access,category,slug']]);
        try {
            // 人の審査では、管理者が「採用」にした AI の整形案だけを取り込む(チェックの欄がなければ、投稿のまま)
            if ($request->has('adopt_shown')) {
                $adopt = [];
                foreach ($request->array('adopt') as $field) {
                    if (is_string($field)) {
                        $adopt[] = $field;
                    }
                }
                app(SuggestionApplier::class)->apply($submission, $submission->ai_result ?? [], $adopt);
            }
            $this->review->approve($submission, $this->user($request));
        } catch (InvalidSubmissionTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.review')->with('status', __('submission.admin_approved', ['receipt' => $submission->receipt_no]));
    }

    public function reject(Request $request, Submission $submission): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:300'], 'note' => ['nullable', 'string', 'max:300']]);
        $reason = trim($request->string('reason')->toString().' '.$request->string('note')->toString());

        try {
            $this->review->reject($submission, $this->user($request), $reason !== '' ? $reason : null);
        } catch (InvalidSubmissionTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.review')->with('status', __('submission.admin_rejected', ['receipt' => $submission->receipt_no]));
    }

    /** 却下ボックスから、審査待ちに戻す(公開はされない) */
    public function restore(Request $request, Submission $submission): RedirectResponse
    {
        try {
            $this->review->restore($submission, $this->user($request));
        } catch (InvalidSubmissionTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.review.show', $submission)->with('status', __('submission.admin_restored'));
    }

    public function rejected(): View
    {
        return view('admin.review.rejected', [
            'submissions' => Submission::query()->whereIn('status', [SubmissionStatus::Rejected, SubmissionStatus::AutoRejected])->with('user')->orderBy('expires_at')->paginate(20),
        ]);
    }

    /** 修正依頼の一覧(審査待ちのもの。承認・却下は審査の詳細で行う) */
    public function corrections(): View
    {
        $submissions = Submission::query()->where('type', SubmissionType::Correction)->where('status', SubmissionStatus::InReview)->with(['corrections', 'user'])->orderBy('id')->paginate(20);

        $current = [];
        foreach ($submissions as $submission) {
            $current[$submission->id] = $this->currentValue($submission);
        }

        // AI が自動で反映した修正は「要確認」として残す(管理者が確認するか、戻す)
        $autoApplied = Submission::query()->where('type', SubmissionType::Correction)->where('status', SubmissionStatus::Approved)->where('auto_decision', 'approved')->whereNull('reviewed_by')->with('corrections')->orderByDesc('id')->limit(50)->get();

        return view('admin.review.corrections', ['submissions' => $submissions, 'current' => $current, 'autoApplied' => $autoApplied]);
    }

    /** 自動で反映された修正を、確認した(「要確認」から外す) */
    public function confirmCorrection(Request $request, Submission $submission): RedirectResponse
    {
        abort_unless($submission->type === SubmissionType::Correction && $submission->auto_decision === 'approved' && $submission->reviewed_by === null, 404);
        $submission->forceFill(['reviewed_by' => $this->user($request)->id, 'reviewed_at' => now()])->save();

        return back()->with('status', __('submission.admin_confirmed'));
    }

    /** 自動で反映された修正を、元に戻す(履歴に「版を戻した」として残る) */
    public function rollbackCorrection(Request $request, Submission $submission, RevisionService $revisions): RedirectResponse
    {
        abort_unless($submission->type === SubmissionType::Correction && $submission->auto_decision === 'approved' && $submission->reviewed_by === null, 404);
        $correction = $submission->corrections()->firstOrFail();
        $revision = Revision::query()->find($correction->applied_revision_id);
        $class = CorrectionFields::model($correction->target_type);
        $target = $class === null ? null : $class::query()->find($correction->target_id);
        abort_if($revision === null || $target === null, 404);

        $revisions->rollback($revision, $target, $this->user($request));
        $submission->forceFill(['reviewed_by' => $this->user($request)->id, 'reviewed_at' => now()])->save();

        return back()->with('status', __('submission.admin_rolled_back'));
    }

    /** 元の画像の確認。管理者だけが、認証つきのこの経路で見られる(公開側から届く経路は作らない) */
    public function original(Media $media): StreamedResponse
    {
        $original = $media->original;
        abort_if($original === null || ! Storage::disk($original->disk)->exists($original->path), 404);

        return Storage::disk($original->disk)->response($original->path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Disposition' => 'inline']);
    }

    /** 修正依頼の対象の、いまの値 */
    private function currentValue(Submission $submission): string
    {
        $correction = $submission->corrections->first();
        if ($correction === null) {
            return '';
        }
        $class = CorrectionFields::model($correction->target_type);
        $value = $class === null ? null : $class::query()->find($correction->target_id)?->getAttribute($correction->field);

        return is_scalar($value) ? (string) $value : '';
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }
}
