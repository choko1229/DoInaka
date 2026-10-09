<?php

declare(strict_types=1);

use App\Services\Region\RegionSlugger;
use App\Services\Text\Romanizer;
use App\Support\ReservedSlugs;

beforeEach(function (): void {
    $this->slugger = new RegionSlugger(new Romanizer);
});

it('都道府県は、読みから語尾(県・都・府)を除く。北海道はそのまま', function (): void {
    expect($this->slugger->forPrefecture('香川県', 'カガワケン'))->toBe('kagawa')
        ->and($this->slugger->forPrefecture('東京都', 'トウキョウト'))->toBe('tokyo')
        ->and($this->slugger->forPrefecture('京都府', 'キョウトフ'))->toBe('kyoto')
        ->and($this->slugger->forPrefecture('大阪府', 'オオサカフ'))->toBe('osaka')
        ->and($this->slugger->forPrefecture('北海道', 'ホッカイドウ'))->toBe('hokkaido');
});

it('市町村は、種別の語尾の読みを除いたローマ字になる', function (): void {
    $slugs = $this->slugger->forSiblings([
        ['key' => 'a', 'name' => '丸亀市', 'kana' => 'マルガメシ'],
        ['key' => 'b', 'name' => '小豆島町', 'kana' => 'ショウドシマチョウ'],
        ['key' => 'c', 'name' => 'まんのう町', 'kana' => 'マンノウチョウ'],
        ['key' => 'd', 'name' => '東かがわ市', 'kana' => 'ヒガシカガワシ'],
        ['key' => 'e', 'name' => '宇多津町', 'kana' => 'ウタヅチョウ'],
    ]);

    expect($slugs)->toBe(['a' => 'marugame', 'b' => 'shodoshima', 'c' => 'manno', 'd' => 'higashikagawa', 'e' => 'utazu']);
});

it('同じ親の中で読みが重なったときだけ、-shi / -cho / -son を付ける', function (): void {
    $slugs = $this->slugger->forSiblings([
        ['key' => 'city', 'name' => '国分寺市', 'kana' => 'コクブンジシ'],
        ['key' => 'town', 'name' => '国分寺町', 'kana' => 'コクブンジチョウ'],
        ['key' => 'other', 'name' => '高松市', 'kana' => 'タカマツシ'],
        ['key' => 'village', 'name' => '大川村', 'kana' => 'オオカワソン'],
        ['key' => 'townB', 'name' => '大川町', 'kana' => 'オオカワチョウ'],
    ]);

    expect($slugs['city'])->toBe('kokubunji-shi')
        ->and($slugs['town'])->toBe('kokubunji-cho')
        ->and($slugs['other'])->toBe('takamatsu')
        ->and($slugs['village'])->toBe('okawa-son')
        ->and($slugs['townB'])->toBe('okawa-cho');
});

it('予約語と同じスラッグは作れない(種別を付ける)', function (): void {
    $slugs = $this->slugger->forSiblings([
        ['key' => 'x', 'name' => 'イベント町', 'kana' => 'イベンツチョウ'],
    ]);
    expect(ReservedSlugs::isReserved('events'))->toBeTrue()
        ->and(ReservedSlugs::isReserved('MAP'))->toBeTrue()
        ->and(ReservedSlugs::isReserved('takamatsu'))->toBeFalse();

    // 読みが予約語になる名前(ここでは「マップ町」→ map ではなく mappu になるので、予約語そのものに当たる名前で確かめる)
    foreach (['events', 'series', 'spots', 'articles', 'map', 'post', 'report', 'login', 'mypage', 'users', 'terms', 'privacy', 'about', 'contact', 'admin', 'api'] as $word) {
        expect(ReservedSlugs::isReserved($word))->toBeTrue($word);
    }
    expect($slugs['x'])->not->toBe('');
});

it('同じ名前・種別で時代が違うものは、時代を付けて区別し、それでも重なれば数を付ける', function (): void {
    $slugs = $this->slugger->forSiblings([
        ['key' => '1', 'name' => '長尾町', 'kana' => 'ながおちょう', 'era' => 'heisei'],
        ['key' => '2', 'name' => '長尾町', 'kana' => 'ながおちょう', 'era' => 'showa'],
        ['key' => '3', 'name' => '長尾町', 'kana' => 'ながおちょう', 'era' => 'showa'],
    ]);

    expect($slugs['1'])->toBe('nagao-cho-heisei')
        ->and($slugs['2'])->toBe('nagao-cho-showa')
        ->and($slugs['3'])->toBe('nagao-cho-showa-2')
        ->and(count(array_unique($slugs)))->toBe(3);
});

it('結果のスラッグは、同じ親の中で必ず一意になる', function (): void {
    $siblings = [];
    foreach (range(1, 30) as $i) {
        $siblings[] = ['key' => "k{$i}", 'name' => '同町', 'kana' => 'ドウチョウ'];
    }

    $slugs = $this->slugger->forSiblings($siblings);

    expect(count(array_unique($slugs)))->toBe(30);
});
