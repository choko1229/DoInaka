<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Models\Article;
use App\Models\Event;
use App\Models\Region;
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
        'region' => ['intro_body'],
    ];

    /** @var array<string, class-string<Model>> */
    private const MODELS = ['event' => Event::class, 'spot' => Spot::class, 'article' => Article::class, 'region' => Region::class];

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

    /** 修正依頼の対象にできるか(イベント・スポット・記事は公開中、地域は有効) */
    public static function isOpen(Model $target): bool
    {
        return $target instanceof Region ? $target->is_active : (bool) $target->getAttribute('is_published');
    }

    /** url は http(s) だけ。ほかの項目は文字だけ */
    public static function isUrlField(string $field): bool
    {
        return $field === 'url';
    }
}
