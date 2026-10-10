<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Contracts\SearchEngine;
use App\Enums\SubmissionType;
use App\Models\Submission;
use App\Services\Search\SearchQuery;
use Illuminate\Support\Str;

/**
 * 重複の候補をDBで絞る(AI に探させない。設計書9.2)。同じ種類で、タイトルが検索に当たるものの上位3件(要約だけ)。
 */
final class DuplicateFinder
{
    public function __construct(private readonly SearchEngine $search) {}

    /** @return list<array{id: int, title: string, summary: string}> */
    public function candidates(Submission $submission): array
    {
        $title = $submission->text('title');
        if ($title === null || ! in_array($submission->type, [SubmissionType::Spot, SubmissionType::Article], true)) {
            return [];
        }

        $query = new SearchQuery(q: mb_substr($title, 0, 30), perPage: 3);
        /** @var list<array{id: int, title: string, summary: string}> $out */
        $out = [];
        if ($submission->type === SubmissionType::Spot) {
            foreach ($this->search->spots($query)->items() as $spot) {
                $out[] = ['id' => $spot->id, 'title' => $spot->title, 'summary' => Str::limit((string) $spot->body, 80, '')];
            }
        } else {
            foreach ($this->search->articles($query)->items() as $article) {
                $out[] = ['id' => $article->id, 'title' => $article->title, 'summary' => Str::limit((string) $article->body, 80, '')];
            }
        }

        return $out;
    }
}
