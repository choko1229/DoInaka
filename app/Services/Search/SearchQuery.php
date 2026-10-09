<?php

declare(strict_types=1);

namespace App\Services\Search;

/**
 * 検索の条件(画面・API が同じ形で渡す。設計書7章・11章)。
 */
final readonly class SearchQuery
{
    public const SORTS = ['date', 'popular', 'distance'];

    public const PRESETS = ['today', 'weekend'];

    public const MAX_PER_PAGE = 50;

    /**
     * @param  list<int>|null  $regionIds  この地域(県・市町・旧町村とその中)に絞る
     */
    public function __construct(
        public ?array $regionIds = null,
        public ?string $q = null,
        public ?int $categoryId = null,
        public ?string $tag = null,
        public ?string $preset = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public bool $includePast = false,
        public ?float $lat = null,
        public ?float $lng = null,
        public ?float $radiusKm = null,
        public string $sort = 'date',
        public int $page = 1,
        public int $perPage = 12,
    ) {}

    public function hasLocation(): bool
    {
        return $this->lat !== null && $this->lng !== null && $this->radiusKm !== null;
    }

    public function hasDateFilter(): bool
    {
        return $this->preset !== null || $this->dateFrom !== null || $this->dateTo !== null;
    }

    /** 検索語(正規化・空白区切り)の数など、「条件が付いているか」(一覧の noindex の判定に使う) */
    public function isFiltered(): bool
    {
        return ($this->q !== null && $this->q !== '') || $this->categoryId !== null || $this->tag !== null
            || $this->hasDateFilter() || $this->hasLocation() || $this->includePast || $this->sort !== 'date';
    }
}
