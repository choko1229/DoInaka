<?php

declare(strict_types=1);

use App\Contracts\SearchEngine;
use App\Enums\EventSourceKind;
use App\Enums\SettingKey;
use App\Models\Article;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\Spot;
use App\Models\Tag;
use App\Services\Content\ContentService;
use App\Services\Search\SearchQuery;
use App\Services\Setting\SettingsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 公開済みの開催回を、日程つきで作る(検索用のテキストも作る)。
 *
 * @param  list<string>  $dates
 */
function searchEvent(array $dates, array $attributes = [], array $tags = [], array $times = []): Event
{
    $title = $attributes['title'] ?? '行事';
    $series = isset($attributes['series_id'])
        ? EventSeries::query()->findOrFail($attributes['series_id'])
        : EventSeries::factory()->create(['title' => $title, 'summary' => null, 'region_id' => $attributes['region_id'] ?? Region::factory()->create()->id]);
    // 検索の語に当たらないよう、本文・会場は空にしておく(必要なものだけ attributes で渡す)
    $event = Event::factory()->create(array_merge(['series_id' => $series->id, 'body' => null, 'venue_name' => null], $attributes, ['series_id' => $series->id]));
    foreach ($dates as $i => $date) {
        $event->schedules()->create(['date' => $date, 'start_time' => $times[$i] ?? '09:00', 'end_time' => '15:00']);
    }
    foreach ($tags as $name) {
        $event->tags()->attach(Tag::query()->firstOrCreate(['name' => $name], ['slug' => 'tag-'.md5($name)])->id);
    }
    $event->sources()->create(['kind' => EventSourceKind::Url, 'url' => 'https://example.com', 'title' => '情報元']);
    $event->publish();
    app(ContentService::class)->refreshSearchText($event->refresh());

    return $event->refresh();
}

/** @return list<int> */
function ids(LengthAwarePaginator $page): array
{
    return collect($page->items())->pluck('id')->all();
}

beforeEach(function (): void {
    // 試験は1つのトランザクションの中で動き、InnoDB の全文インデックスはコミットされるまで見えないので、LIKE 版で試す(ngram は別のテスト)
    app(SettingsService::class)->set(SettingKey::SearchDriver, 'like');
    Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00', 'Asia/Tokyo'));
    $this->engine = app(SearchEngine::class);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('「獅子舞」で獅子舞の行事が出て、関係ないものは出ない(読みが違っても出る)', function (): void {
    $match = searchEvent(['2026-10-20'], ['title' => '獅子舞奉納']);
    $kana = searchEvent(['2026-10-21'], ['title' => 'ししまいの夕べ']);
    $other = searchEvent(['2026-10-22'], ['title' => '棚田の稲刈り体験', 'body' => '朝から刈ります']);

    $result = ids($this->engine->events(new SearchQuery(q: '獅子舞')));
    expect($result)->toContain($match->id)->not->toContain($other->id);

    // ひらがなで検索しても、「獅子舞」の記事は「ししまい」のページに当たる
    expect(ids($this->engine->events(new SearchQuery(q: 'ししまい'))))->toContain($kana->id)->not->toContain($other->id);
    // 全角英数・半角カナも同じ
    expect(ids($this->engine->events(new SearchQuery(q: 'ｼｼﾏｲ'))))->toContain($kana->id);
});

it('複数の語は、すべてを含むものだけ(AND)。1文字の語も使える。LIKE の記号は文字として扱う', function (): void {
    $a = searchEvent(['2026-10-20'], ['title' => '獅子舞奉納', 'venue_name' => '八幡神社']);
    $b = searchEvent(['2026-10-21'], ['title' => '獅子舞体験', 'venue_name' => '公民館']);
    $c = searchEvent(['2026-10-22'], ['title' => '祭り100%', 'venue_name' => '港']);

    expect(ids($this->engine->events(new SearchQuery(q: '獅子舞 八幡'))))->toBe([$a->id])
        ->and(ids($this->engine->events(new SearchQuery(q: '港'))))->toBe([$c->id])
        ->and(ids($this->engine->events(new SearchQuery(q: '100%'))))->toBe([$c->id])
        ->and(ids($this->engine->events(new SearchQuery(q: '%'))))->toBe([$c->id])
        ->and(ids($this->engine->events(new SearchQuery(q: '_'))))->toBe([]);
    expect($b->id)->toBeInt();
});

it('終わった開催回は、「過去も含む」を選んだときだけ出る。並びは次の日程が近い順', function (): void {
    $past = searchEvent(['2026-10-01']);
    $soon = searchEvent(['2026-10-14']);
    $later = searchEvent(['2026-11-20']);

    expect(ids($this->engine->events(new SearchQuery)))->toBe([$soon->id, $later->id]);
    // 過去も含む: これからの日程が近い順、そのあとに終わったもの(新しい順)
    expect(ids($this->engine->events(new SearchQuery(includePast: true))))->toBe([$soon->id, $later->id, $past->id]);
});

it('「今日」は、今日の日程を持つ開催回', function (): void {
    $today = searchEvent(['2026-10-12']);
    $tomorrow = searchEvent(['2026-10-13']);

    expect(ids($this->engine->events(new SearchQuery(preset: 'today'))))->toBe([$today->id]);
    expect($tomorrow->id)->toBeInt();
});

it('「今週末」は、土日と、つながる祝日・振替休日、連休前の金曜の夜を含む', function (): void {
    // 2026-10-12(月)はスポーツの日。今は月曜の昼で、連休(10/10〜10/12)の最終日の途中
    DB::table('holidays')->insert(['date' => '2026-10-12', 'name' => 'スポーツの日', 'created_at' => now(), 'updated_at' => now()]);
    Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00', 'Asia/Tokyo')); // 木曜: 今週末は 10/10(土)〜10/12(月・祝)

    $fridayNight = searchEvent(['2026-10-09'], times: ['18:00']);
    $fridayDay = searchEvent(['2026-10-09'], times: ['10:00']);
    $saturday = searchEvent(['2026-10-10']);
    $sunday = searchEvent(['2026-10-11']);
    $holidayMonday = searchEvent(['2026-10-12']);
    $tuesday = searchEvent(['2026-10-13']);
    $nextWeek = searchEvent(['2026-10-17']);

    $result = ids($this->engine->events(new SearchQuery(preset: 'weekend')));

    expect($result)->toContain($fridayNight->id, $saturday->id, $sunday->id, $holidayMonday->id)
        ->not->toContain($fridayDay->id)
        ->not->toContain($tuesday->id)
        ->not->toContain($nextWeek->id);
});

it('日曜の深夜までが今週末で、月曜になると次の週末になる', function (): void {
    $thisWeekend = searchEvent(['2026-10-17', '2026-10-18']);
    $nextWeekend = searchEvent(['2026-10-24']);

    Carbon::setTestNow(Carbon::parse('2026-10-18 23:59:00', 'Asia/Tokyo'));
    expect(ids($this->engine->events(new SearchQuery(preset: 'weekend'))))->toBe([$thisWeekend->id]);

    Carbon::setTestNow(Carbon::parse('2026-10-19 00:00:00', 'Asia/Tokyo'));
    expect(ids($this->engine->events(new SearchQuery(preset: 'weekend'))))->toBe([$nextWeekend->id]);
});

it('期間指定は、期間内に1日でも日程がある開催回を返す', function (): void {
    $inside = searchEvent(['2026-10-20', '2026-10-21']);
    $overlapStart = searchEvent(['2026-10-15', '2026-10-25']);
    $outside = searchEvent(['2026-11-30']);

    $result = ids($this->engine->events(new SearchQuery(dateFrom: '2026-10-21', dateTo: '2026-10-25')));

    expect($result)->toContain($inside->id, $overlapStart->id)->not->toContain($outside->id);
    expect(ids($this->engine->events(new SearchQuery(dateFrom: '2026-11-01'))))->toBe([$outside->id]);
});

it('地域で絞れる(県・市町・旧町村とその中)', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'name' => '高松市']);
    $other = Region::factory()->create(['parent_id' => $pref->id, 'name' => '丸亀市']);
    $inCity = searchEvent(['2026-10-20'], ['region_id' => $city->id]);
    $inOther = searchEvent(['2026-10-21'], ['region_id' => $other->id]);
    $elsewhere = searchEvent(['2026-10-22']);

    expect(ids($this->engine->events(new SearchQuery(regionIds: [$pref->id, $city->id, $other->id]))))->toContain($inCity->id, $inOther->id)->not->toContain($elsewhere->id)
        ->and(ids($this->engine->events(new SearchQuery(regionIds: [$city->id]))))->toBe([$inCity->id])
        ->and(ids($this->engine->events(new SearchQuery(regionIds: []))))->toBe([]);
});

it('分類・タグで絞れる', function (): void {
    $category = Category::factory()->create();
    $a = searchEvent(['2026-10-20'], ['category_id' => $category->id], ['獅子舞']);
    $b = searchEvent(['2026-10-21'], [], ['体験']);

    expect(ids($this->engine->events(new SearchQuery(categoryId: $category->id))))->toBe([$a->id])
        ->and(ids($this->engine->events(new SearchQuery(tag: '体験'))))->toBe([$b->id])
        ->and(ids($this->engine->events(new SearchQuery(tag: 'なし'))))->toBe([]);
});

it('半径10km で、四角の角にある10kmより遠い場所は除かれる(距離順)', function (): void {
    // 中心: 高松駅の近く
    [$lat, $lng] = [34.3500, 134.0470];
    $near = searchEvent(['2026-10-20'], ['lat' => $lat + 0.036, 'lng' => $lng]);          // 真北に約4km
    $edge = searchEvent(['2026-10-21'], ['lat' => $lat, 'lng' => $lng + 0.100]);          // 真東に約9.1km(半径内)
    $corner = searchEvent(['2026-10-22'], ['lat' => $lat + 0.085, 'lng' => $lng + 0.105]); // 四角の中だが、約13.5km(半径外)
    $far = searchEvent(['2026-10-23'], ['lat' => $lat + 0.5, 'lng' => $lng]);              // 四角の外
    $noLocation = searchEvent(['2026-10-24']);                                              // 緯度経度なし

    $page = $this->engine->events(new SearchQuery(lat: $lat, lng: $lng, radiusKm: 10, sort: 'distance'));

    expect(ids($page))->toBe([$near->id, $edge->id])
        ->and(ids($page))->not->toContain($corner->id, $far->id, $noLocation->id);

    $distances = collect($page->items())->map(fn (Event $e): float => round((float) $e->getAttribute('distance_km'), 1))->all();
    expect($distances[0])->toBeGreaterThan(3.5)->toBeLessThan(4.5)
        ->and($distances[1])->toBeGreaterThan(8.5)->toBeLessThan(9.6);

    // 半径を大きくすれば角も入る
    expect(ids($this->engine->events(new SearchQuery(lat: $lat, lng: $lng, radiusKm: 15, sort: 'distance'))))->toContain($corner->id);
});

it('人気順は popularity_score の大きい順', function (): void {
    $low = searchEvent(['2026-10-20']);
    $high = searchEvent(['2026-10-21']);
    $low->forceFill(['popularity_score' => 3])->saveQuietly();
    $high->forceFill(['popularity_score' => 50])->saveQuietly();

    expect(ids($this->engine->events(new SearchQuery(sort: 'popular'))))->toBe([$high->id, $low->id]);
});

it('非公開・削除済みは出ない', function (): void {
    $published = searchEvent(['2026-10-20']);
    $hidden = searchEvent(['2026-10-21']);
    $hidden->unpublish();
    $deleted = searchEvent(['2026-10-22']);
    $deleted->unpublish();
    $deleted->delete();

    expect(ids($this->engine->events(new SearchQuery)))->toBe([$published->id]);
});

it('1ページの件数は上限50で、ページを進められる', function (): void {
    foreach (range(1, 5) as $i) {
        searchEvent(['2026-10-'.(12 + $i)]);
    }

    $first = $this->engine->events(new SearchQuery(perPage: 2, page: 1));
    $third = $this->engine->events(new SearchQuery(perPage: 2, page: 3));

    expect($first->total())->toBe(5)->and($first->perPage())->toBe(2)->and(count($first->items()))->toBe(2)
        ->and(count($third->items()))->toBe(1)
        ->and($this->engine->events(new SearchQuery(perPage: 500))->perPage())->toBe(50);
});

it('スポットと記事も、キーワード・地域・分類・距離で検索できる', function (): void {
    $region = Region::factory()->create();
    $content = app(ContentService::class);
    $spot = $content->saveSpot(null, ['title' => '獅子の井戸', 'region_id' => $region->id, 'lat' => 34.35, 'lng' => 134.05, 'is_published' => true], []);
    $far = $content->saveSpot(null, ['title' => '獅子の遠い池', 'region_id' => $region->id, 'lat' => 35.35, 'lng' => 134.05, 'is_published' => true], []);
    $draft = $content->saveSpot(null, ['title' => '獅子の下書き', 'region_id' => $region->id, 'is_published' => false], []);
    $article = $content->saveArticle(null, ['title' => '獅子舞を見てきた', 'region_id' => $region->id, 'is_published' => true], ['体験'], []);

    expect(ids($this->engine->spots(new SearchQuery(q: '獅子'))))->toContain($spot->id, $far->id)->not->toContain($draft->id)
        ->and(ids($this->engine->spots(new SearchQuery(q: '獅子', lat: 34.35, lng: 134.05, radiusKm: 10, sort: 'distance'))))->toBe([$spot->id])
        ->and(ids($this->engine->articles(new SearchQuery(q: '獅子舞'))))->toBe([$article->id])
        ->and(ids($this->engine->articles(new SearchQuery(tag: '体験'))))->toBe([$article->id])
        ->and(ids($this->engine->articles(new SearchQuery(regionIds: [$region->id + 999]))))->toBe([]);
    expect(Spot::query()->count())->toBe(3)->and(Article::query()->count())->toBe(1);
});

it('ngram 全文検索でも、「獅子舞」で獅子舞の行事が出て、関係ないものは出ない(コミット後の本物のインデックス)', function (): void {
    // InnoDB の全文インデックスはコミットされるまで見えない。トランザクションを確定し、あとで作ったものを消す
    DB::commit();
    app(SettingsService::class)->set(SettingKey::SearchDriver, 'ngram');

    $region = Region::factory()->create();
    $series = EventSeries::factory()->create(['title' => '行事', 'summary' => null, 'region_id' => $region->id]);

    try {
        $match = searchEvent(['2026-10-20'], ['title' => '獅子舞奉納', 'region_id' => $region->id, 'series_id' => $series->id]);
        $kana = searchEvent(['2026-10-21'], ['title' => 'ししまいの夕べ', 'region_id' => $region->id, 'series_id' => $series->id]);
        $other = searchEvent(['2026-10-22'], ['title' => '棚田の稲刈り体験', 'region_id' => $region->id, 'series_id' => $series->id]);
        $matchIds = [$match->id, $kana->id, $other->id];
        DB::commit(); // 作ったものを確定(全文インデックスに載せる)

        $result = ids($this->engine->events(new SearchQuery(q: '獅子舞')));
        expect($result)->toContain($match->id)->not->toContain($other->id);
        expect(ids($this->engine->events(new SearchQuery(q: 'ししまい'))))->toContain($kana->id)->not->toContain($other->id);
        // 検索の記号は、演算子として働かない
        expect(fn () => $this->engine->events(new SearchQuery(q: '獅子舞 -"(*) @ ~')))->not->toThrow(Throwable::class);
    } finally {
        $ids = Event::withTrashed()->where('series_id', $series->id)->pluck('id');
        DB::table('events')->whereIn('id', $ids)->update(['is_published' => 0]);
        DB::table('event_sources')->whereIn('event_id', $ids)->delete();
        DB::table('event_schedules')->whereIn('event_id', $ids)->delete();
        DB::table('taggables')->whereIn('taggable_id', $ids)->where('taggable_type', 'event')->delete();
        DB::table('revisions')->where('revisionable_type', 'event')->whereIn('revisionable_id', $ids)->delete();
        DB::table('events')->whereIn('id', $ids)->update(['is_published' => 0]);
        DB::table('events')->whereIn('id', $ids)->delete();
        DB::table('event_series')->where('id', $series->id)->delete();
        DB::table('regions')->where('id', $region->id)->delete();
        DB::table('settings')->delete();
        DB::table('tags')->where('name', 'like', 'tag-%')->delete();
        Cache::flush();
    }
});
