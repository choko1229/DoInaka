<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Article;
use App\Models\Event;
use App\Models\Region;
use App\Models\Spot;

/**
 * 検索用の search_text(正規化済みのテキスト。ngram 全文インデックスの対象)を作る(設計書3章・11章)。
 *
 * 正規化: HTML タグを除く → 半角カナ・全角英数を揃える(NFKC 相当)→ ひらがなをカタカナに → 英字は小文字 → 空白をひとつに。
 * 検索側の入力にも、同じ normalize() を通す(フェーズ4)。
 */
final class SearchTextBuilder
{
    public function normalize(string $text): string
    {
        $text = strip_tags($text);
        // K: 半角カナ→全角カナ、V: 濁点の結合、a: 全角英数→半角、s: 全角空白→半角、C: ひらがな→カタカナ
        $text = mb_convert_kana($text, 'KVasC', 'UTF-8');
        $text = mb_strtolower($text, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /**
     * @param  iterable<mixed>  $parts
     */
    public function build(iterable $parts): string
    {
        $pieces = [];
        foreach ($parts as $part) {
            if (is_string($part) && $part !== '') {
                $pieces[] = $this->normalize($part);
            }
        }

        return implode(' ', array_filter($pieces, fn (string $p): bool => $p !== ''));
    }

    /**
     * search_text を作り直して保存する(更新日時は動かさない)。
     */
    public function refresh(Event|Spot|Article $model): void
    {
        $model->unsetRelation('tags')->unsetRelation('region')->unsetRelation('category')->unsetRelation('series');
        $text = match (true) {
            $model instanceof Event => $this->forEvent($model),
            $model instanceof Spot => $this->forSpot($model),
            default => $this->forArticle($model),
        };

        $model->forceFill(['search_text' => $text])->saveQuietly();
    }

    public function forEvent(Event $event): string
    {
        $event->loadMissing(['series', 'region.parent', 'category', 'tags']);

        return $this->build([
            $event->title,
            $event->series->title ?? null,
            $event->body,
            $event->venue_name,
            $event->address,
            $event->fee,
            ...$this->regionNames($event->region),
            $event->category?->name,
            ...$event->tags->pluck('name')->all(),
        ]);
    }

    public function forSpot(Spot $spot): string
    {
        $spot->loadMissing(['region.parent', 'category', 'tags']);

        return $this->build([
            $spot->title, $spot->body, $spot->address, $spot->hours, $spot->access,
            ...$this->regionNames($spot->region),
            $spot->category?->name,
            ...$spot->tags->pluck('name')->all(),
        ]);
    }

    public function forArticle(Article $article): string
    {
        $article->loadMissing(['region.parent', 'tags']);

        return $this->build([
            $article->title, $article->body,
            ...$this->regionNames($article->region),
            ...$article->tags->pluck('name')->all(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function regionNames(?Region $region): array
    {
        $names = [];
        for ($node = $region; $node !== null; $node = $node->parent) {
            $names[] = $node->name;
        }

        return $names;
    }
}
