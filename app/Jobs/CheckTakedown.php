<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\AiRateLimited;
use App\Jobs\Concerns\DefersWhenAiPaused;
use App\Models\Inquiry;
use App\Services\Takedown\TakedownChecker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * 削除依頼の AI による照合(目安を出すだけ。削除はしない)。
 */
final class CheckTakedown implements ShouldQueue
{
    use DefersWhenAiPaused;
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $inquiryId) {}

    public function handle(TakedownChecker $checker): void
    {
        $inquiry = Inquiry::query()->find($this->inquiryId);
        if ($inquiry === null || ($inquiry->ai_check['status'] ?? null) === 'ok') {
            return;
        }

        $wait = $this->secondsUntilAiResumes();
        if ($wait !== null) {
            $this->release($wait);

            return;
        }

        try {
            $checker->check($inquiry);
        } catch (AiRateLimited $e) {
            $this->release($this->secondsUntilTime($e->retryAt));
        }
    }
}
