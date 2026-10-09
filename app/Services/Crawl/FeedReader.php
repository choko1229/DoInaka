<?php

declare(strict_types=1);

namespace App\Services\Crawl;

/**
 * RSS / Atom の項目のリンクを取り出す(新しい項目のリンク先を、詳細ページとして読む。設計書9.6)。
 */
final class FeedReader
{
    /** @return list<string> */
    public static function links(string $xml): array
    {
        // DOCTYPE(外部・内部のエンティティの定義)を含むものは読まない(XXE・エンティティ展開の攻撃への備え)
        if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        // 外部エンティティは読まない(XXE 対策)。LIBXML_NONET でネットワークも使わない
        $feed = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($feed === false) {
            return [];
        }

        $links = [];
        foreach ($feed->channel->item ?? [] as $item) {
            $links[] = trim((string) $item->link);
        }
        foreach ($feed->entry ?? [] as $entry) {
            foreach ($entry->link ?? [] as $link) {
                $rel = (string) ($link['rel'] ?? 'alternate');
                if ($rel === 'alternate') {
                    $links[] = trim((string) ($link['href'] ?? ''));
                }
            }
        }

        return array_values(array_unique(array_filter($links, fn (string $l): bool => preg_match('#^https?://#i', $l) === 1)));
    }
}
