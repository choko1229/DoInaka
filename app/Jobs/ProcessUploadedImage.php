<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\UploadRejected;
use App\Models\Submission;
use App\Services\Image\ImageProcessor;
use App\Services\Submission\SubmissionPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 投稿された画像から公開用の WebP を作る(設計書12.1)。失敗しても投稿は止めず、人の審査に回して、原因を記録する。
 */
final class ProcessUploadedImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $submissionId) {}

    public function handle(ImageProcessor $processor, SubmissionPipeline $pipeline): void
    {
        $submission = Submission::query()->find($this->submissionId);
        if ($submission === null) {
            return;
        }

        $failed = [];
        foreach ($submission->media as $media) {
            if ($media->isProcessed()) {
                continue;
            }
            try {
                $processor->process($media);
            } catch (UploadRejected $e) {
                $failed[] = $media->id;
                $media->forceFill(['ai_result' => ['error' => $e->getMessage()]])->save();
            } catch (Throwable $e) {
                $failed[] = $media->id;
                Log::warning('画像の処理に失敗しました。', ['media_id' => $media->id, 'exception' => $e::class]);
                $media->forceFill(['ai_result' => ['error' => 'process_failed']])->save();
            }
        }

        if ($failed !== []) {
            $payload = $submission->payload ?? [];
            $payload['image_error_media_ids'] = $failed;
            $submission->forceFill(['payload' => $payload])->save();
        }

        $pipeline->afterImages($submission);
    }
}
