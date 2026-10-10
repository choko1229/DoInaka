<?php

declare(strict_types=1);

use App\Services\Text\Romanizer;

it('ヘボン式の変換表どおりに変換する(長音の記号は付けない)', function (string $kana, string $expected): void {
    expect((new Romanizer)->toRomaji($kana))->toBe($expected);
})->with([
    // 基本・濁音・半濁音
    ['アイウエオ', 'aiueo'], ['カキクケコ', 'kakikukeko'], ['サシスセソ', 'sashisuseso'], ['タチツテト', 'tachitsuteto'],
    ['ナニヌネノ', 'naninuneno'], ['ハヒフヘホ', 'hahifuheho'], ['マミムメモ', 'mamimumemo'], ['ヤユヨ', 'yayuyo'],
    ['ラリルレロ', 'rarirurero'], ['ワヲン', 'waon'], ['ガギグゲゴ', 'gagigugego'], ['ザジズゼゾ', 'zajizuzezo'],
    ['ダヂヅデド', 'dajizudedo'], ['バビブベボ', 'babibubebo'], ['パピプペポ', 'papipupepo'],
    // 拗音
    ['キャキュキョ', 'kyakyukyo'], ['シャシュショ', 'shashusho'], ['チャチュチョ', 'chachucho'], ['ニャニュニョ', 'nyanyunyo'],
    ['ヒャヒュヒョ', 'hyahyuhyo'], ['ミャミュミョ', 'myamyumyo'], ['リャリュリョ', 'ryaryuryo'], ['ギャギュギョ', 'gyagyugyo'],
    ['ジャジュジョ', 'jajujo'], ['ビャビュビョ', 'byabyubyo'], ['ピャピュピョ', 'pyapyupyo'],
    // 外来音
    ['ファフィフェフォ', 'fafifefo'], ['ティ', 'ti'], ['ウィ', 'wi'],
    // 促音(ッ): 子音を重ねる。ch の前は t
    ['ニッポン', 'nippon'], ['サッポロ', 'sapporo'], ['キッチョウ', 'kitcho'], ['ホッカイドウ', 'hokkaido'], ['ザッシ', 'zasshi'],
    // 長音: ou / oo / uu は のばさない
    ['トウキョウ', 'tokyo'], ['ショウドシマ', 'shodoshima'], ['オオサカ', 'osaka'], ['キョウト', 'kyoto'], ['ユウビン', 'yubin'],
    ['ラーメン', 'ramen'], ['コーヒー', 'kohi'],
    // ン
    ['シンジュク', 'shinjuku'], ['ホンダ', 'honda'], ['ミンナ', 'minna'],
    // ひらがなも受け付ける
    ['たかまつ', 'takamatsu'], ['まるがめ', 'marugame'], ['ぞうごうむら', 'zogomura'],
    // 地名
    ['タカマツ', 'takamatsu'], ['マルガメ', 'marugame'], ['サカイデ', 'sakaide'], ['ゼンツウジ', 'zentsuji'], ['カンオンジ', 'kanonji'],
    ['サヌキ', 'sanuki'], ['ヒガシカガワ', 'higashikagawa'], ['ミトヨ', 'mitoyo'], ['トノショウ', 'tonosho'], ['ナオシマ', 'naoshima'],
    ['ウタヅ', 'utazu'], ['コトヒラ', 'kotohira'], ['タドツ', 'tadotsu'], ['マンノウ', 'manno'], ['アヤガワ', 'ayagawa'],
]);

it('かなでない文字は、英数字とハイフン以外を捨てる', function (): void {
    $r = new Romanizer;

    expect($r->toRomaji('香川'))->toBe('')
        ->and($r->toRomaji('タカマツ-2'))->toBe('takamatsu-2')
        ->and($r->toRomaji('ＡＢＣカ'))->toBe('abcka')
        ->and($r->toRomaji(' タ カ '))->toBe('taka')
        ->and($r->toRomaji(''))->toBe('');
});

it('半角カナも変換する', function (): void {
    expect((new Romanizer)->toRomaji('ｶﾞﾜ'))->toBe('gawa');
});
