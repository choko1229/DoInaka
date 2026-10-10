<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Article;
use App\Models\Event;
use App\Models\Spot;
use App\Services\Search\SearchQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 検索エンジン(設計書2.2)。MVP は MySQL(ngram 全文または LIKE)。将来 Meilisearch などに差し替える。
 */
interface SearchEngine
{
    /** @return LengthAwarePaginator<int, Event> */
    public function events(SearchQuery $query): LengthAwarePaginator;

    /** @return LengthAwarePaginator<int, Spot> */
    public function spots(SearchQuery $query): LengthAwarePaginator;

    /** @return LengthAwarePaginator<int, Article> */
    public function articles(SearchQuery $query): LengthAwarePaginator;
}
