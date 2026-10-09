<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Models\Article;
use App\Models\Event;
use App\Models\Spot;
use Illuminate\Database\Eloquent\Model;

/**
 * 修正依頼で直せる項目(設計書9.7)と、直す先のモデル。ここにない項目は、依頼の段階で断る。
 */
final class CorrectionFields
{
    /** @var array<string, list<string>> */
    private const FIELDS = [
        'event' => ['title', 'venue_name', 'address', 'fee', 'url', 'body'],
        'spot' => ['title', 'address', 'hours', 'access', 'url', 'body'],
        'article' => ['title', 'body'],
    ];

    /** @var array<string, class-string<Model>> */
    private const MODELS = ['event' => Event::class, 'spot' => Spot::class, 'article' => Article::class];

    /** @return list<string> */
    public static function for(string $targetType): array
    {
        return self::FIELDS[$targetType] ?? [];
    }

    /** @return list<string> */
    public static function targetTypes(): array
    {
        return array_keys(self::FIELDS);
    }

    /** @return class-string<Model>|null */
    public static function model(string $targetType): ?string
    {
        return self::MODELS[$targetType] ?? null;
    }

    /** url は http(s) だけ。ほかの項目は文字だけ */
    public static function isUrlField(string $field): bool
    {
        return $field === 'url';
    }
}
