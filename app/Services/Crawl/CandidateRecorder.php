<?php

declare(strict_types=1);

namespace App\Services\Crawl;

use App\Models\CrawlCandidate;
use App\Models\CrawlSource;
use App\Models\Region;
use App\Models\Submission;

/**
 * 情報提供の URL を、情報源の「候補」に載せる(設計書9.6・9.7)。定期巡回には入れず、管理者が登録か無視を選ぶ。
 * すでに情報源にあるホスト・すでに候補にあるものは、載せない。
 */
final class CandidateRecorder
{
    public function fromTip(Submission $submission): ?CrawlCandidate
    {
        $url = $submission->text('source_url');
        $host = $url === null ? null : parse_url($url, PHP_URL_HOST);
        if ($url === null || ! is_string($host)) {
            return null;
        }
        $host = strtolower($host);

        if (CrawlSource::query()->where('host', $host)->exists() || CrawlCandidate::query()->where('host', $host)->exists()) {
            return null;
        }

        $region = $submission->number('region_id') > 0 ? Region::query()->where('id', $submission->number('region_id'))->first() : null;

        $candidate = new CrawlCandidate;
        $candidate->forceFill([
            'url' => mb_substr($url, 0, 500), 'host' => $host, 'origin' => 'tip', 'submission_id' => $submission->id,
            'region_id' => $region?->id, 'status' => 'new',
        ])->save();

        return $candidate;
    }
}
