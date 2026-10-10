<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Enums\CategoryTarget;
use App\Models\Category;
use App\Models\Region;
use App\Services\Region\RegionScope;
use App\Services\Search\SearchQuery;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * URL のクエリを検索条件にする。範囲外・不正な値は黙って捨てる(エラーにしない。設計書11章)。
 *
 * クエリ: q, category(スラッグ), tag, when(today|weekend), from, to(Y-m-d), past, lat, lng, r(km), sort, page
 */
final class SearchQueryFactory
{
    public const MIN_RADIUS_KM = 1.0;

    public const MAX_RADIUS_KM = 50.0;

    public function __construct(private readonly RegionScope $scope) {}

    public function fromRequest(Request $request, ?Region $region = null, CategoryTarget $target = CategoryTarget::Event, ?string $fixedPreset = null, ?int $fixedCategoryId = null): SearchQuery
    {
        $q = $this->text($request->query('q'), 100);
        $tag = $this->text($request->query('tag'), 60);

        $categoryId = $fixedCategoryId;
        $categorySlug = $this->text($request->query('category'), 60);
        if ($categoryId === null && $categorySlug !== null) {
            $categoryId = Category::query()->where('target', $target)->where('slug', $categorySlug)->where('is_active', true)->value('id');
            $categoryId = is_numeric($categoryId) ? (int) $categoryId : null;
        }

        $preset = $fixedPreset;
        $when = $request->query('when');
        if ($preset === null && is_string($when) && in_array($when, SearchQuery::PRESETS, true)) {
            $preset = $when;
        }

        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));
        if ($from !== null && $to !== null && $to < $from) {
            [$from, $to] = [$to, $from];
        }
        if ($preset !== null) {
            // 日付の絞り込みは、プリセットか範囲のどちらか一方
            $from = $to = null;
        }

        [$lat, $lng, $radius] = $this->location($request);

        $sort = $request->query('sort');
        $sort = is_string($sort) && in_array($sort, SearchQuery::SORTS, true) ? $sort : 'date';
        if ($sort === 'distance' && $lat === null) {
            $sort = 'date';
        }

        $page = $request->query('page');
        $page = is_string($page) && ctype_digit($page) ? min(1000, max(1, (int) $page)) : 1;

        return new SearchQuery(
            regionIds: $region === null ? null : $this->scope->ids($region),
            q: $q,
            categoryId: $categoryId,
            tag: $tag,
            preset: $preset,
            dateFrom: $from,
            dateTo: $to,
            includePast: $request->boolean('past'),
            lat: $lat,
            lng: $lng,
            radiusKm: $radius,
            sort: $sort,
            page: $page,
            perPage: 12,
        );
    }

    private function text(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim((string) preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', $value));

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }
        try {
            $d = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
        if ($d === null || $d->format('Y-m-d') !== $value || $d->year < 2000 || $d->year > 2100) {
            return null;
        }

        return $value;
    }

    /** @return array{float|null, float|null, float|null} */
    private function location(Request $request): array
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $r = $request->query('r');
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return [null, null, null];
        }
        $lat = (float) $lat;
        $lng = (float) $lng;
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return [null, null, null];
        }
        $radius = is_numeric($r) ? (float) $r : 10.0;
        $radius = min(self::MAX_RADIUS_KM, max(self::MIN_RADIUS_KM, $radius));

        return [$lat, $lng, $radius];
    }
}
