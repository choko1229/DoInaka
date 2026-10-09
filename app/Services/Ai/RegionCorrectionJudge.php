<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiPurpose;
use App\Enums\SubmissionStatus;
use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\AiUnavailable;
use App\Models\Region;
use App\Models\Submission;
use App\Services\Submission\ReviewService;
use App\Services\Submission\SubmissionStateMachine;
use App\Services\Web\FetchResult;
use App\Services\Web\UrlFetcher;

/**
 * 地域ページの紹介文への修正依頼(設計書9.8): AI が情報元(送られた URL か、いまの出典)と照合し、
 * 提案された文のすべてに裏付けがあれば、自動で直して履歴に残す。なければ保留して、管理者が見る。
 */
final class RegionCorrectionJudge
{
    public function __construct(
        private readonly AiClient $ai,
        private readonly PromptRepository $prompts,
        private readonly UrlFetcher $fetcher,
        private readonly ReviewService $review,
        private readonly SubmissionStateMachine $machine,
    ) {}

    /**
     * @throws AiRateLimited
     */
    public function judge(Submission $submission): void
    {
        $correction = $submission->corrections()->first();
        $region = $correction === null ? null : Region::query()->find($correction->target_id);
        $proposed = $correction?->proposed_value;

        if ($correction === null || $region === null || $proposed === null || trim($proposed) === '') {
            $this->hold($submission, 'no_target');

            return;
        }

        $sources = $this->sources($region, $correction->source_url);
        if ($sources === []) {
            $this->hold($submission, 'no_sources');

            return;
        }

        $sentences = array_values(array_filter(array_map('trim', preg_split('/(?<=。)/u', $proposed) ?: []), fn (string $s): bool => $s !== ''));
        $prompt = $this->prompts->get('fact_check');
        $user = Data::wrap('確かめる文', collect($sentences)->map(fn (string $s, int $i): string => "[{$i}] {$s}")->implode("\n"));
        foreach ($sources as $n => $text) {
            $user .= "\n\n".Data::wrap('情報元 ['.($n + 1).']', $text);
        }

        try {
            $result = $this->ai->run(new AiRequest(AiPurpose::FactCheck, $prompt['text'], $user, ['results' => 'array'], null, $submission->id, $prompt['version']));
        } catch (AiRateLimited $e) {
            throw $e;
        } catch (AiUnavailable|AiBadResponse|AiRequestFailed) {
            $this->hold($submission, 'ai_failed');

            return;
        }

        $supported = [];
        foreach (is_array($result['results']) ? $result['results'] : [] as $row) {
            if (is_array($row) && is_numeric($row['index'] ?? null) && ($row['supported'] ?? false) === true) {
                $supported[(int) $row['index']] = true;
            }
        }

        $all = $sentences !== [] && count($supported) === count($sentences) && array_keys($supported) === array_keys($sentences);
        $submission->forceFill(['ai_status' => 'ok', 'ai_result' => ['supported' => count($supported), 'total' => count($sentences)], 'auto_decision' => $all ? 'approved' : null])->save();

        if ($all) {
            $this->review->approve($submission, null);

            return;
        }

        // 裏付けのない修正は、自動で反映しない(管理者が見る)
        $this->machine->transition($submission, SubmissionStatus::InReview);
    }

    /**
     * 情報元の本文: 送られた URL と、いまの出典(intro_sources の URL)。
     *
     * @return list<string>
     */
    private function sources(Region $region, ?string $sourceUrl): array
    {
        $urls = array_filter([$sourceUrl, ...array_map(fn (mixed $s): ?string => is_array($s) && is_string($s['url'] ?? null) ? $s['url'] : null, $region->intro_sources ?? [])]);
        $texts = [];
        foreach (array_slice(array_unique($urls), 0, 4) as $url) {
            $page = $this->fetcher->fetch($url);
            if ($page->status === FetchResult::OK && $page->text() !== '') {
                $texts[] = mb_substr($page->text(), 0, 6000);
            }
        }

        return $texts;
    }

    private function hold(Submission $submission, string $reason): void
    {
        $submission->forceFill(['ai_status' => 'failed', 'ai_result' => ['reason' => $reason]])->save();
        $this->machine->transition($submission, SubmissionStatus::InReview);
    }
}
