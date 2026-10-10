<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\SubmissionStatus;
use App\Exceptions\AiRateLimited;
use App\Jobs\Concerns\DefersWhenAiPaused;
use App\Models\Submission;
use App\Services\Ai\SubmissionJudge;
use App\Services\Submission\SubmissionStateMachine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * 投稿1件の AI 判定(優先順位2)。制限エラーのときは失敗にせず、「翌日へ延期」にして、リセットのあとに再開する(ai:resume)。
 * そのほかの失敗(AI が壊れている・キューの例外)でも、投稿は失われず、人の審査に回す。
 */
final class JudgeSubmission implements ShouldQueue
{
    use DefersWhenAiPaused;
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $submissionId) {}

    public function handle(SubmissionJudge $judge, SubmissionStateMachine $machine): void
    {
        $submission = Submission::query()->with(['user', 'media'])->find($this->submissionId);
        if ($submission === null || $submission->status !== SubmissionStatus::AiPending) {
            return;
        }

        if ($this->secondsUntilAiResumes() !== null) {
            $machine->transition($submission, SubmissionStatus::AiDeferred);

            return;
        }

        try {
            $judge->judge($submission);
        } catch (AiRateLimited) {
            $machine->transition($submission->refresh(), SubmissionStatus::AiDeferred);
        }
    }

    /** キューの想定外の例外でも、投稿は人の審査に回す(失われない) */
    public function failed(Throwable $e): void
    {
        $submission = Submission::query()->find($this->submissionId);
        if ($submission !== null && $submission->status === SubmissionStatus::AiPending) {
            app(SubmissionJudge::class)->toHumanReview($submission, 'failed', $e::class);
        }
    }
}
