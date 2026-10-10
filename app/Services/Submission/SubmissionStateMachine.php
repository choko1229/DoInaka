<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Enums\AuditAction;
use App\Enums\SubmissionStatus;
use App\Exceptions\InvalidSubmissionTransition;
use App\Models\Submission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Str;

/**
 * 投稿の状態遷移(設計書8章)。status を書き換えるのは、ここだけ。
 *
 * 受付 → 画像処理 → AI判定待ち →(自動承認 | 審査待ち | 自動却下 | 翌日へ延期)
 * 人の審査: 審査待ち → 承認 | 却下。却下・自動却下 → 審査待ち(元に戻す)。
 * AI判定待ち・延期中も、管理者は手動で承認・却下できる。
 */
final class SubmissionStateMachine
{
    /** 却下ボックスに残す日数(設計書8章。経過したら物理削除) */
    public const REJECTED_RETENTION_DAYS = 90;

    /** @var array<string, list<SubmissionStatus>> */
    private const ALLOWED = [
        'received' => [SubmissionStatus::Processing, SubmissionStatus::AiPending, SubmissionStatus::InReview],
        'processing' => [SubmissionStatus::AiPending, SubmissionStatus::InReview],
        'ai_pending' => [SubmissionStatus::Approved, SubmissionStatus::InReview, SubmissionStatus::AutoRejected, SubmissionStatus::AiDeferred, SubmissionStatus::Rejected],
        'ai_deferred' => [SubmissionStatus::AiPending, SubmissionStatus::Approved, SubmissionStatus::Rejected, SubmissionStatus::InReview],
        'in_review' => [SubmissionStatus::Approved, SubmissionStatus::Rejected],
        'auto_rejected' => [SubmissionStatus::InReview],
        'rejected' => [SubmissionStatus::InReview],
        'approved' => [],
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * 受付: 新しい投稿を「受付」の状態で作る。
     *
     * @param  array<string, mixed>  $attributes
     */
    public function open(array $attributes): Submission
    {
        $submission = new Submission($attributes);
        $submission->forceFill(['status' => SubmissionStatus::Received])->save();

        return $submission;
    }

    public function can(Submission $submission, SubmissionStatus $to): bool
    {
        return in_array($to, self::ALLOWED[$submission->status->value], true);
    }

    /**
     * 状態を移す。許されない遷移は InvalidSubmissionTransition。
     * 人の操作($actor あり)は、審査者と日時・理由を残し、操作ログにも書く。
     */
    public function transition(Submission $submission, SubmissionStatus $to, ?User $actor = null, ?string $reason = null): Submission
    {
        $from = $submission->status;
        if (! $this->can($submission, $to)) {
            throw InvalidSubmissionTransition::between($from, $to);
        }

        $attributes = ['status' => $to];

        if ($to->isRejected()) {
            // 却下ボックスの期限(90日)。復元されたら期限は外す
            $attributes['expires_at'] = now()->addDays(self::REJECTED_RETENTION_DAYS);
            $attributes['reject_reason'] = $reason === null ? null : Str::limit($reason, 300, '');
        } elseif ($from->isRejected()) {
            $attributes['expires_at'] = null;
            $attributes['reject_reason'] = null;
        }

        if ($actor !== null && in_array($to, [SubmissionStatus::Approved, SubmissionStatus::Rejected, SubmissionStatus::InReview], true)) {
            $attributes['reviewed_by'] = $actor->id;
            $attributes['reviewed_at'] = now();
        }

        $submission->forceFill($attributes)->save();

        if ($actor !== null) {
            $this->audit->record(AuditAction::SubmissionReview, $actor, 'submission', $submission->id, ['from' => $from->value, 'to' => $to->value]);
        }

        return $submission;
    }
}
