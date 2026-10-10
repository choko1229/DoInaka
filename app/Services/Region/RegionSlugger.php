<?php

declare(strict_types=1);

namespace App\Services\Region;

use App\Services\Text\Romanizer;
use App\Support\ReservedSlugs;

/**
 * 地域のスラッグ(読みのローマ字)を決める(設計書9.8)。
 *
 * - 市区町村・旧町村は、種別の語尾(市・町・村・区)の読みを除いたローマ字(丸亀市 → marugame)
 * - 同じ親の中で重なったときだけ、種別を表す -shi / -ku / -cho / -son を付ける。それでも重なれば、時代(-heisei / -showa)、最後に数
 * - events / series / spots / articles / map などの予約語は使わない(使うことになるときは種別を付ける)
 */
final class RegionSlugger
{
    /** 種別 → 語尾の読み(ひらがな・カタカナどちらでも。あとで正規化する) */
    private const SUFFIX_KANA = [
        '市' => ['シ'],
        '区' => ['ク'],
        '町' => ['チョウ', 'マチ'],
        '村' => ['ソン', 'ムラ'],
    ];

    private const SUFFIX_SLUG = ['市' => 'shi', '区' => 'ku', '町' => 'cho', '村' => 'son'];

    public function __construct(private readonly Romanizer $romanizer) {}

    /** 都道府県: 読みから語尾(ケン・ト・フ)を除く。北海道だけはそのまま */
    public function forPrefecture(string $name, string $kana): string
    {
        $kana = mb_convert_kana($kana, 'KVC');

        if ($name === '北海道') {
            return $this->romanizer->toRomaji($kana);
        }

        foreach (['ケン', 'ト', 'フ'] as $suffix) {
            if (str_ends_with($kana, $suffix) && mb_strlen($kana) > mb_strlen($suffix)) {
                return $this->romanizer->toRomaji(mb_substr($kana, 0, mb_strlen($kana) - mb_strlen($suffix)));
            }
        }

        return $this->romanizer->toRomaji($kana);
    }

    /**
     * 同じ親の下に並ぶ地域のスラッグをまとめて決める。
     *
     * @param  list<array{key: string, name: string, kana: string, era?: string|null}>  $siblings  key は呼び出し側で決めた一意の印
     * @return array<string, string> key → スラッグ
     */
    public function forSiblings(array $siblings): array
    {
        /** @var array<string, array{base: string, kind: string, era: string|null}> $info */
        $info = [];
        $counts = [];

        foreach ($siblings as $row) {
            $kind = $this->kindOf($row['name']);
            $base = $this->baseSlug($row['name'], $row['kana'], $kind);
            $info[$row['key']] = ['base' => $base, 'kind' => $kind, 'era' => $row['era'] ?? null];
            $counts[$base] = ($counts[$base] ?? 0) + 1;
        }

        // 1回目: 重なる・予約語のものにだけ種別を付ける
        $slugs = [];
        foreach ($info as $key => $item) {
            $needsSuffix = $counts[$item['base']] > 1 || ReservedSlugs::isReserved($item['base']) || $item['base'] === '';
            $slugs[$key] = $needsSuffix ? $this->withKind($item['base'], $item['kind']) : $item['base'];
        }

        // 2回目: まだ重なるものに時代を付ける。それでも重なれば数を付ける
        $seen = [];
        foreach ($slugs as $key => $slug) {
            $seen[$slug] = ($seen[$slug] ?? 0) + 1;
        }
        $used = [];
        foreach ($slugs as $key => $slug) {
            if ($seen[$slug] > 1 && $info[$key]['era'] !== null) {
                $slug .= '-'.$info[$key]['era'];
            }
            $slugs[$key] = $slug;
        }

        foreach ($slugs as $key => $slug) {
            $candidate = $slug;
            $n = 2;
            while (isset($used[$candidate])) {
                $candidate = $slug.'-'.$n++;
            }
            $used[$candidate] = true;
            $slugs[$key] = $candidate;
        }

        return $slugs;
    }

    /** 名前の末尾(市・区・町・村)。どれでもなければ空 */
    public function kindOf(string $name): string
    {
        $last = mb_substr($name, -1);

        return isset(self::SUFFIX_KANA[$last]) ? $last : '';
    }

    private function baseSlug(string $name, string $kana, string $kind): string
    {
        $kana = mb_convert_kana($kana, 'KVC');

        foreach (self::SUFFIX_KANA[$kind] ?? [] as $suffix) {
            if (str_ends_with($kana, $suffix) && mb_strlen($kana) > mb_strlen($suffix)) {
                $kana = mb_substr($kana, 0, mb_strlen($kana) - mb_strlen($suffix));
                break;
            }
        }

        return $this->romanizer->toRomaji($kana);
    }

    private function withKind(string $base, string $kind): string
    {
        $suffix = self::SUFFIX_SLUG[$kind] ?? null;

        return $suffix === null ? $base : ($base === '' ? $suffix : $base.'-'.$suffix);
    }
}
