<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Contracts\AiReviewGate;
use App\Enums\AiPurpose;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Jobs\JudgeSubmission;
use App\Jobs\ProcessUploadedImage;
use App\Models\Submission;

/**
 * 受け付けたあとの流れ(設計書8章): 画像があれば画像処理 → AI判定待ち(使えるとき) → 審査待ち。
 * AI が使えない・止まっているときは、人の審査に回す(フェーズ5は常にこちら)。
 */
final class SubmissionPipeline
{
    /** 公開用に画像を処理する種類(チラシ写真の tip は、個人情報を隠すまで処理しない) */
    private const PUBLIC_IMAGE_TYPES = [SubmissionType::Spot, SubmissionType::Article, SubmissionType::VisitPhoto];

    public function __construct(
        private readonly SubmissionStateMachine $machine,
        private readonly AiReviewGate $ai,
    ) {}

    public function afterIntake(Submission $submission): void
    {
        $hasImages = in_array($submission->type, self::PUBLIC_IMAGE_TYPES, true) && $submission->media()->exists();

        if ($hasImages) {
            $this->machine->transition($submission, SubmissionStatus::Processing);
            ProcessUploadedImage::dispatch($submission->id)->onQueue('high');

            return;
        }

        $this->readyForJudgement($submission);
    }

    /** 画像の処理が終わった(または失敗した)あと、次へ進める */
    public function afterImages(Submission $submission): void
    {
        $this->readyForJudgement($submission->refresh());
    }

    private function readyForJudgement(Submission $submission): void
    {
        if (! $this->ai->available()) {
            $this->machine->transition($submission, SubmissionStatus::InReview);

            return;
        }

        $this->machine->transition($submission, SubmissionStatus::AiPending);
        JudgeSubmission::dispatch($submission->id)->onQueue(AiPurpose::ReviewText->queue());
    }
}
