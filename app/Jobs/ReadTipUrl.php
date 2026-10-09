<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AiPurpose;
use App\Enums\SubmissionStatus;
use App\Exceptions\AiRateLimited;
use App\Jobs\Concerns\DefersWhenAiPaused;
use App\Models\CrawlSource;
use App\Models\Submission;
use App\Services\Ai\DraftService;
use App\Services\Crawl\CrawlImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * 情報提供の URL を1ページだけ読んで、イベントの下書きを作る(優先順位3。設計書9.7)。
 * 下書きは審査の画面(情報提供)に出す。URL が「信頼済みの情報源」のサイトのときだけ、巡回と同じ条件で自動公開の対象にする。
 */
final class ReadTipUrl implements ShouldQueue
{
    use DefersWhenAiPaused;
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $submissionId) {}

    public function handle(DraftService $drafts, CrawlImporter $importer): void
    {
        $tip = Submission::query()->find($this->submissionId);
        $url = $tip?->text('source_url');
        if ($tip === null || $url === null || ! in_array($tip->status, [SubmissionStatus::InReview, SubmissionStatus::AiPending, SubmissionStatus::AiDeferred], true)) {
            return;
        }
        // robots.txt が禁止している URL は、読まない(管理者の確認に回す)
        if (($tip->inspection()['status'] ?? null) !== 'ok') {
            return;
        }

        $wait = $this->secondsUntilAiResumes();
        if ($wait !== null) {
            $this->release($wait);

            return;
        }

        try {
            $result = $drafts->fromUrl($url, AiPurpose::Tip, $tip->id);
        } catch (AiRateLimited $e) {
            $this->release($this->secondsUntilTime($e->retryAt));

            return;
        }

        $tip->forceFill(['ai_status' => $result['status'] === 'ok' ? 'ok' : 'failed', 'ai_result' => $result['status'] === 'ok' ? ['draft' => $result['draft']->toArray()] : ['error' => $result['reason']]])->save();

        if ($result['status'] !== 'ok') {
            return;
        }

        // 信頼済みの情報源のサイトなら、巡回と同じ条件で取り込む(それ以外は、人が確認する)
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $source = CrawlSource::query()->with('region')->where('host', $host)->where('is_trusted', true)->where('is_active', true)->whereNull('paused_at')->first();
        if ($source !== null) {
            $importer->import($source, $result['draft'], $url);
        }
    }
}
