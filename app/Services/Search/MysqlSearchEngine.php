<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Contracts\SearchEngine;
use App\Enums\SettingKey;
use App\Models\Article;
use App\Models\Event;
use App\Models\Spot;
use App\Services\Calendar\WeekendResolver;
use App\Services\Setting\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * MySQL の検索(設計書11章)。
 *
 * - キーワード: ngram 全文(MATCH ... AGAINST)。2文字未満の語、または設定 search.driver が like のときは、search_text への LIKE
 * - 日付: event_schedules に対して判定(今日・今週末(連休を含む)・期間)。終わった開催回は「過去も含む」を選んだときだけ
 * - 距離: 緯度経度の四角で絞ってから、ハーバーサイン式で距離を計算する(現在地は保存しない・ログに出さない)
 * - 並び: 次の日程が近い順(既定)・人気順・距離順
 */
class MysqlSearchEngine implements SearchEngine
{
    private const EARTH_RADIUS_KM = 6371.0;

    public function __construct(
        private readonly SettingsService $settings,
        private readonly SearchTextBuilder $text,
        private readonly WeekendResolver $weekend,
    ) {}

    public function events(SearchQuery $query): LengthAwarePaginator
    {
        $today = $this->today();
        $builder = Event::query()->where('events.is_published', true);

        $this->scope($builder, 'events', $query);
        $this->keyword($builder, 'events', $query);
        $this->dates($builder, $query, $today);

        $builder->select('events.*')
            ->selectSub(
                DB::table('event_schedules')->selectRaw('MIN(date)')->whereColumn('event_id', 'events.id')->where('date', '>=', $today->toDateString()),
                'next_date',
            )
            ->selectSub(DB::table('event_schedules')->selectRaw('MAX(date)')->whereColumn('event_id', 'events.id'), 'last_date');

        $this->distance($builder, 'events', $query);

        match (true) {
            $query->sort === 'distance' && $query->hasLocation() => $builder->orderBy('distance_km'),
            $query->sort === 'popular' => $builder->orderByDesc('events.popularity_score')->orderByDesc('events.id'),
            // 次の日程が近い順。これからの日程がないもの(過去も含むとき)は、最後の日程が新しい順に後ろへ
            default => $builder->orderByRaw('next_date IS NULL')->orderBy('next_date')->orderByDesc('last_date')->orderByDesc('events.id'),
        };

        $builder->with(['region.parent', 'category', 'schedules', 'tags', 'media']);

        return $builder->paginate($this->perPage($query), ['*'], 'page', max(1, $query->page));
    }

    public function spots(SearchQuery $query): LengthAwarePaginator
    {
        $builder = Spot::query()->where('spots.is_published', true);

        $this->scope($builder, 'spots', $query);
        $this->keyword($builder, 'spots', $query);
        $builder->select('spots.*');
        $this->distance($builder, 'spots', $query);
        $this->order($builder, 'spots', $query);

        $builder->with(['region.parent', 'category', 'tags', 'media']);

        return $builder->paginate($this->perPage($query), ['*'], 'page', max(1, $query->page));
    }

    public function articles(SearchQuery $query): LengthAwarePaginator
    {
        $builder = Article::query()->where('articles.is_published', true);

        $this->scope($builder, 'articles', $query, hasCategory: false);
        $this->keyword($builder, 'articles', $query);
        $builder->select('articles.*');
        $this->order($builder, 'articles', $query);

        $builder->with(['region.parent', 'tags', 'media']);

        return $builder->paginate($this->perPage($query), ['*'], 'page', max(1, $query->page));
    }

    /**
     * @param  Builder<Event>|Builder<Spot>|Builder<Article>  $builder
     */
    private function scope(Builder $builder, string $table, SearchQuery $query, bool $hasCategory = true): void
    {
        if ($query->regionIds !== null) {
            $builder->whereIn("{$table}.region_id", $query->regionIds === [] ? [0] : $query->regionIds);
        }

        $categoryIds = array_values(array_unique([...($query->categoryId !== null ? [$query->categoryId] : []), ...$query->categoryIds]));
        if ($hasCategory && $categoryIds !== []) {
            $builder->whereIn("{$table}.category_id", $categoryIds);
        }

        if ($query->tag !== null && $query->tag !== '') {
            $type = match ($table) {
                'events' => 'event',
                'spots' => 'spot',
                default => 'article',
            };
            $builder->whereExists(function (QueryBuilder $sub) use ($table, $query, $type): void {
                $sub->select(DB::raw(1))->from('taggables')->join('tags', 'tags.id', '=', 'taggables.tag_id')
                    ->whereColumn('taggables.taggable_id', "{$table}.id")->where('taggables.taggable_type', $type)
                    ->where(fn (QueryBuilder $t) => $t->where('tags.name', $query->tag)->orWhere('tags.slug', $query->tag));
            });
        }
    }

    /**
     * @param  Builder<Event>|Builder<Spot>|Builder<Article>  $builder
     */
    private function keyword(Builder $builder, string $table, SearchQuery $query): void
    {
        if ($query->q === null || trim($query->q) === '') {
            return;
        }

        $terms = array_values(array_filter(explode(' ', $this->text->normalize($query->q)), fn (string $t): bool => $t !== ''));
        $useNgram = $this->settings->string(SettingKey::SearchDriver) === 'ngram';

        $boolean = [];
        foreach ($terms as $term) {
            if ($useNgram && mb_strlen($term) >= 2) {
                // ngram(トークン長2)。引用符で囲み、記号を検索の演算子として読ませない
                $boolean[] = '+"'.str_replace(['"', '+', '-', '<', '>', '(', ')', '~', '*', '@'], ' ', $term).'"';
            } else {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
                $builder->where("{$table}.search_text", 'like', $like);
            }
        }

        if ($boolean !== []) {
            $builder->whereRaw(DB::raw("MATCH({$table}.search_text) AGAINST (? IN BOOLEAN MODE)"), [implode(' ', $boolean)]); // @phpstan-ignore argument.type
        }
    }

    /**
     * @param  Builder<Event>  $builder
     */
    private function dates(Builder $builder, SearchQuery $query, CarbonImmutable $today): void
    {
        $schedules = fn (): QueryBuilder => DB::table('event_schedules')->select(DB::raw(1))->whereColumn('event_schedules.event_id', 'events.id');

        if ($query->preset === 'today') {
            $builder->whereExists($schedules()->where('event_schedules.date', $today->toDateString()));

            return;
        }

        if ($query->preset === 'weekend') {
            $range = $this->weekend->resolve($today);
            $builder->whereExists(function (QueryBuilder $sub) use ($range): void {
                $sub->select(DB::raw(1))->from('event_schedules')->whereColumn('event_schedules.event_id', 'events.id')
                    ->where(function (QueryBuilder $w) use ($range): void {
                        $w->whereBetween('event_schedules.date', [$range['from']->toDateString(), $range['to']->toDateString()]);
                        // 連休の前の金曜の夜(17時以降に始まる日程)
                        if ($range['friday_night'] !== null) {
                            $w->orWhere(fn (QueryBuilder $f) => $f->where('event_schedules.date', $range['friday_night']->toDateString())
                                ->where('event_schedules.start_time', '>=', WeekendResolver::FRIDAY_NIGHT_FROM));
                        }
                    });
            });

            return;
        }

        if ($query->dateFrom !== null || $query->dateTo !== null) {
            $from = $query->dateFrom ?? '0001-01-01';
            $to = $query->dateTo ?? '9999-12-31';
            // 期間内に1日でも日程がある開催回
            $builder->whereExists($schedules()->whereBetween('event_schedules.date', [$from, $to]));

            return;
        }

        if (! $query->includePast) {
            $builder->whereExists($schedules()->where('event_schedules.date', '>=', $today->toDateString()));
        }
    }

    /**
     * @param  Builder<Event>|Builder<Spot>  $builder
     */
    private function distance(Builder $builder, string $table, SearchQuery $query): void
    {
        if (! $query->hasLocation() || $query->lat === null || $query->lng === null || $query->radiusKm === null) {
            return;
        }

        $lat = $query->lat;
        $lng = $query->lng;
        $radius = $query->radiusKm;

        // 1. 緯度経度の四角で絞る(インデックスが効く)
        $dLat = $radius / 111.32;
        $dLng = $radius / (111.32 * max(0.01, cos(deg2rad($lat))));
        $builder->whereBetween("{$table}.lat", [$lat - $dLat, $lat + $dLat])->whereBetween("{$table}.lng", [$lng - $dLng, $lng + $dLng]);

        // 2. 候補だけ球面距離を計算して、半径内に絞る(数値は float 型で受けているので、そのまま式に埋め込んでよい)
        $haversine = sprintf(
            '%F * ACOS(LEAST(1, COS(RADIANS(%F)) * COS(RADIANS(%s.lat)) * COS(RADIANS(%s.lng) - RADIANS(%F)) + SIN(RADIANS(%F)) * SIN(RADIANS(%s.lat))))',
            self::EARTH_RADIUS_KM, $lat, $table, $table, $lng, $lat, $table,
        );
        $builder->addSelect(DB::raw("{$haversine} AS distance_km")); // @phpstan-ignore argument.type
        $builder->whereRaw(DB::raw(sprintf('%s <= %F', $haversine, $radius))); // @phpstan-ignore argument.type
    }

    /**
     * @param  Builder<Spot>|Builder<Article>  $builder
     */
    private function order(Builder $builder, string $table, SearchQuery $query): void
    {
        match (true) {
            $query->sort === 'distance' && $query->hasLocation() && $table === 'spots' => $builder->orderBy('distance_km'),
            $query->sort === 'popular' => $builder->orderByDesc("{$table}.popularity_score")->orderByDesc("{$table}.id"),
            default => $builder->orderByDesc("{$table}.published_at")->orderByDesc("{$table}.id"),
        };
    }

    private function perPage(SearchQuery $query): int
    {
        return max(1, min(SearchQuery::MAX_PER_PAGE, $query->perPage));
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now('Asia/Tokyo')->startOfDay();
    }
}
