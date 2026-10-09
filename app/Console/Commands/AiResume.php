<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AiPurpose;
use App\Enums\SubmissionStatus;
use App\Jobs\JudgeSubmission;
use App\Models\Submission;
use App\Services\Ai\AiUsage;
use App\Services\Submission\SubmissionStateMachine;
use Illuminate\Console\Command;

/**
 * 制限エラーで翌日に回した投稿の判定を再開する(UTC 0時のリセットのあと。毎時、止まっていなければ動く)。
 * 優先順(古いものから)に、判定待ちへ戻してキューへ入れる。
 */
final class AiResume extends Command
{
    protected $signature = 'ai:resume';

    protected $description = '制限エラーで延期した投稿の AI 判定を再開する';

    public function handle(AiUsage $usage, SubmissionStateMachine $machine): int
    {
        if ($usage->isPaused()) {
            $this->info(__('ai.paused', ['time' => $usage->pausedUntil()?->setTimezone('Asia/Tokyo')->format('m/d H:i')]));

            return self::SUCCESS;
        }

        $count = 0;
        Submission::query()->where('status', SubmissionStatus::AiDeferred)->orderBy('id')->each(function (Submission $submission) use ($machine, &$count): void {
            $machine->transition($submission, SubmissionStatus::AiPending);
            JudgeSubmission::dispatch($submission->id)->onQueue(AiPurpose::ReviewText->queue());
            $count++;
        });

        $this->info(__('ai.resumed', ['count' => $count]));

        return self::SUCCESS;
    }
}
