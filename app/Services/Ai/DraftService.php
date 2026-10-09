<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiPurpose;
use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\AiUnavailable;
use App\Services\Web\FetchResult;
use App\Services\Web\HtmlText;
use App\Services\Web\UrlFetcher;

/**
 * URL(または情報提供の URL)から、イベントの下書き(事実の項目だけ)を作る(設計書9.2)。
 * ページは1枚だけ読み(robots.txt を守る)、本文の文章は返させない。
 */
final class DraftService
{
    public function __construct(
        private readonly UrlFetcher $fetcher,
        private readonly AiClient $ai,
        private readonly PromptRepository $prompts,
    ) {}

    /**
     * @return array{status: 'ok', draft: EventDraft, title: string|null}|array{status: 'blocked'|'failed'|'unavailable', reason: string}
     *
     * @throws AiRateLimited 制限エラー(呼び出し側が、画面で知らせるか翌日に回す)
     */
    public function fromUrl(string $url, AiPurpose $purpose = AiPurpose::DraftFromUrl, ?int $submissionId = null): array
    {
        $page = $this->fetcher->fetch($url);
        if ($page->status === FetchResult::BLOCKED) {
            return ['status' => 'blocked', 'reason' => __('ai.draft_blocked')];
        }
        if (! $page->ok()) {
            return ['status' => 'failed', 'reason' => __('ai.draft_fetch_failed')];
        }

        $text = $page->text();
        if ($text === '') {
            return ['status' => 'failed', 'reason' => __('ai.draft_empty')];
        }

        $prompt = $this->prompts->get($purpose === AiPurpose::Tip ? 'tip' : 'draft_from_url');

        try {
            $result = $this->ai->run(new AiRequest(
                $purpose, $prompt['text'],
                Data::wrap('判定対象のデータ(Web ページの本文。ページのアドレス: '.$url.')', $text),
                EventDraft::SCHEMA, null, $submissionId, $prompt['version'],
            ));
        } catch (AiRateLimited $e) {
            throw $e;
        } catch (AiUnavailable|AiBadResponse|AiRequestFailed $e) {
            return ['status' => 'unavailable', 'reason' => $e->getMessage()];
        }

        return ['status' => 'ok', 'draft' => EventDraft::fromArray($result), 'title' => HtmlText::title($page->html)];
    }
}
