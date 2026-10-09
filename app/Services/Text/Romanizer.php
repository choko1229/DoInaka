<?php

declare(strict_types=1);

namespace App\Services\Text;

/**
 * 読み(かな)から、URL 用のローマ字(ヘボン式。長音の記号は付けず、「ou」「oo」は「o」にする)を作る。
 * 外部ライブラリは使わず、変換表を自前で持つ(設計書9.5)。
 *
 * 例: タカマツ → takamatsu / ショウドシマ → shodoshima / ホッカイドウ → hokkaido / キッチョウ → kitcho
 */
final class Romanizer
{
    /** 2文字(拗音など)の表。1文字より先に見る */
    private const COMBINATIONS = [
        'キャ' => 'kya', 'キュ' => 'kyu', 'キョ' => 'kyo', 'キェ' => 'kye', 'キィ' => 'kyi',
        'シャ' => 'sha', 'シュ' => 'shu', 'ショ' => 'sho', 'シェ' => 'she',
        'チャ' => 'cha', 'チュ' => 'chu', 'チョ' => 'cho', 'チェ' => 'che',
        'ニャ' => 'nya', 'ニュ' => 'nyu', 'ニョ' => 'nyo',
        'ヒャ' => 'hya', 'ヒュ' => 'hyu', 'ヒョ' => 'hyo',
        'ミャ' => 'mya', 'ミュ' => 'myu', 'ミョ' => 'myo',
        'リャ' => 'rya', 'リュ' => 'ryu', 'リョ' => 'ryo',
        'ギャ' => 'gya', 'ギュ' => 'gyu', 'ギョ' => 'gyo',
        'ジャ' => 'ja', 'ジュ' => 'ju', 'ジョ' => 'jo', 'ジェ' => 'je',
        'ヂャ' => 'ja', 'ヂュ' => 'ju', 'ヂョ' => 'jo',
        'ビャ' => 'bya', 'ビュ' => 'byu', 'ビョ' => 'byo',
        'ピャ' => 'pya', 'ピュ' => 'pyu', 'ピョ' => 'pyo',
        'ファ' => 'fa', 'フィ' => 'fi', 'フェ' => 'fe', 'フォ' => 'fo',
        'ティ' => 'ti', 'ディ' => 'di', 'トゥ' => 'tu', 'ドゥ' => 'du',
        'ウィ' => 'wi', 'ウェ' => 'we', 'ウォ' => 'wo',
        'ヴァ' => 'va', 'ヴィ' => 'vi', 'ヴェ' => 've', 'ヴォ' => 'vo',
    ];

    private const SINGLE = [
        'ア' => 'a', 'イ' => 'i', 'ウ' => 'u', 'エ' => 'e', 'オ' => 'o',
        'カ' => 'ka', 'キ' => 'ki', 'ク' => 'ku', 'ケ' => 'ke', 'コ' => 'ko',
        'サ' => 'sa', 'シ' => 'shi', 'ス' => 'su', 'セ' => 'se', 'ソ' => 'so',
        'タ' => 'ta', 'チ' => 'chi', 'ツ' => 'tsu', 'テ' => 'te', 'ト' => 'to',
        'ナ' => 'na', 'ニ' => 'ni', 'ヌ' => 'nu', 'ネ' => 'ne', 'ノ' => 'no',
        'ハ' => 'ha', 'ヒ' => 'hi', 'フ' => 'fu', 'ヘ' => 'he', 'ホ' => 'ho',
        'マ' => 'ma', 'ミ' => 'mi', 'ム' => 'mu', 'メ' => 'me', 'モ' => 'mo',
        'ヤ' => 'ya', 'ユ' => 'yu', 'ヨ' => 'yo',
        'ラ' => 'ra', 'リ' => 'ri', 'ル' => 'ru', 'レ' => 're', 'ロ' => 'ro',
        'ワ' => 'wa', 'ヰ' => 'i', 'ヱ' => 'e', 'ヲ' => 'o', 'ン' => 'n',
        'ガ' => 'ga', 'ギ' => 'gi', 'グ' => 'gu', 'ゲ' => 'ge', 'ゴ' => 'go',
        'ザ' => 'za', 'ジ' => 'ji', 'ズ' => 'zu', 'ゼ' => 'ze', 'ゾ' => 'zo',
        'ダ' => 'da', 'ヂ' => 'ji', 'ヅ' => 'zu', 'デ' => 'de', 'ド' => 'do',
        'バ' => 'ba', 'ビ' => 'bi', 'ブ' => 'bu', 'ベ' => 'be', 'ボ' => 'bo',
        'パ' => 'pa', 'ピ' => 'pi', 'プ' => 'pu', 'ペ' => 'pe', 'ポ' => 'po',
        'ヴ' => 'vu',
        'ァ' => 'a', 'ィ' => 'i', 'ゥ' => 'u', 'ェ' => 'e', 'ォ' => 'o',
        'ャ' => 'ya', 'ュ' => 'yu', 'ョ' => 'yo', 'ヮ' => 'wa',
    ];

    public function toRomaji(string $kana): string
    {
        // ひらがなはカタカナにそろえ、全角英数・半角カナも正規化する
        $kana = mb_convert_kana($kana, 'KVCa', 'UTF-8');
        $chars = mb_str_split($kana);
        $out = '';
        $count = count($chars);

        for ($i = 0; $i < $count; $i++) {
            $char = $chars[$i];

            // 促音(ッ): 次の子音を重ねる(ch の前は t: kitchō → kitcho)
            if ($char === 'ッ') {
                $next = $this->syllableAt($chars, $i + 1);
                if ($next !== null && $next[0] !== '' && ! in_array($next[0][0], ['a', 'i', 'u', 'e', 'o', 'n'], true)) {
                    $out .= str_starts_with($next[0], 'ch') ? 't' : $next[0][0];
                }

                continue;
            }

            // 長音(ー): 記号は付けず、のばさない
            if ($char === 'ー') {
                continue;
            }

            $syllable = $this->syllableAt($chars, $i);
            if ($syllable === null) {
                // かなでない文字: 英数字とハイフンだけ残す
                if (preg_match('/^[A-Za-z0-9-]$/', $char) === 1) {
                    $out .= strtolower($char);
                }

                continue;
            }

            [$romaji, $length] = $syllable;
            $i += $length - 1;

            // 長音の省略: o + u / o + o、u + u は のばさない(トウキョウ → tokyo、ショウドシマ → shodoshima)
            $previous = $out === '' ? '' : $out[strlen($out) - 1];
            if ($romaji === 'u' && $previous === 'o' && $this->afterOColumn($out)) {
                continue;
            }
            if (($romaji === 'o' && $previous === 'o') || ($romaji === 'u' && $previous === 'u')) {
                continue;
            }

            $out .= $romaji;
        }

        return $out;
    }

    /**
     * @param  list<string>  $chars
     * @return array{0: string, 1: int}|null ローマ字と、使った文字数
     */
    private function syllableAt(array $chars, int $index): ?array
    {
        if (! isset($chars[$index])) {
            return null;
        }

        if (isset($chars[$index + 1])) {
            $pair = $chars[$index].$chars[$index + 1];
            if (isset(self::COMBINATIONS[$pair])) {
                return [self::COMBINATIONS[$pair], 2];
            }
        }

        if (isset(self::SINGLE[$chars[$index]])) {
            return [self::SINGLE[$chars[$index]], 1];
        }

        return null;
    }

    /** 直前の音節が「お段」(o で終わる)で、ウでのばしているとき */
    private function afterOColumn(string $out): bool
    {
        return str_ends_with($out, 'o');
    }
}
