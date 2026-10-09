<?php

declare(strict_types=1);

namespace App\Services\Takedown;

use App\Models\Article;
use App\Models\Event;
use App\Models\Spot;
use App\Services\Url\PublicLinks;
use App\Services\Url\UrlCanonicalizer;
use Illuminate\Database\Eloquent\Model;

/**
 * 削除依頼の URL から、このサイトの行事・スポット・記事を引く。引けなければ null(管理者が手で確かめる)。
 */
final class TargetResolver
{
    public function __construct(private readonly UrlCanonicalizer $urls, private readonly PublicLinks $links) {}

    public function resolve(?string $url): ?Model
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower(is_array($parts) ? (string) ($parts['host'] ?? '') : '');
        $main = strtolower($this->urls->mainHost());
        $share = $this->urls->shareHost();
        if (! in_array($host, [$main, 'www.'.$main, $share, 'www.'.$share, strtolower((string) request()->getHost())], true)) {
            return null;
        }

        if (preg_match('#^/[a-z0-9-]+/(events|spots|articles)/([^/]+)/?$#', (string) ($parts['path'] ?? ''), $m) !== 1) {
            return null;
        }
        $parsed = $this->links->parseItem($m[2]);
        if ($parsed === null) {
            return null;
        }

        return match ($m[1]) {
            'events' => Event::query()->find($parsed['id']),
            'spots' => Spot::query()->find($parsed['id']),
            default => Article::query()->find($parsed['id']),
        };
    }
}
