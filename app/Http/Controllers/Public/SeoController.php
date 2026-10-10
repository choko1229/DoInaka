<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\CategoryTarget;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Event;
use App\Models\Region;
use App\Models\Spot;
use App\Services\Public\MetaBuilder;
use App\Services\Region\RegionPageQueue;
use App\Services\Region\RegionScope;
use App\Services\Url\PublicLinks;
use App\Services\Url\UrlCanonicalizer;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

/**
 * サイトマップ(種類別)と robots.txt(設計書15章)。URL はいつもメインのホストで出す。
 */
final class SeoController extends Controller
{
    private const KINDS = ['lists', 'regions', 'events', 'spots', 'articles'];

    private const LIMIT = 45000;

    public function __construct(
        private readonly UrlCanonicalizer $urls,
        private readonly PublicLinks $links,
        private readonly MetaBuilder $meta,
        private readonly RegionPageQueue $regions,
        private readonly RegionScope $scope,
    ) {}

    public function index(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach (self::KINDS as $kind) {
            $xml .= '<sitemap><loc>'.e($this->urls->canonicalUrl("/sitemap-{$kind}.xml")).'</loc></sitemap>'."\n";
        }

        return $this->xml($xml.'</sitemapindex>');
    }

    public function show(string $kind): Response
    {
        abort_unless(in_array($kind, self::KINDS, true), 404);

        $entries = match ($kind) {
            'lists' => $this->lists(),
            'regions' => $this->regionEntries(),
            'events' => $this->items(Event::query(), fn (Event $m): string => $this->links->event($m), ['region.parent']),
            'spots' => $this->items(Spot::query(), fn (Spot $m): string => $this->links->spot($m), ['region.parent']),
            default => $this->items(Article::query(), fn (Article $m): string => $this->links->article($m), ['region.parent']),
        };

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach (array_slice($entries, 0, self::LIMIT) as $entry) {
            $xml .= '<url><loc>'.e($this->urls->canonicalUrl($entry['path'])).'</loc>';
            if ($entry['lastmod'] !== null) {
                $xml .= '<lastmod>'.e($entry['lastmod']).'</lastmod>';
            }
            $xml .= "</url>\n";
        }

        return $this->xml($xml.'</urlset>');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin/',
            'Disallow: /api/',
            'Disallow: /install/',
            'Disallow: /auth/',
            'Disallow: /login/',
            '',
            'Sitemap: '.$this->urls->canonicalUrl('/sitemap.xml'),
            '',
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** @return list<array{path: string, lastmod: string|null}> */
    private function lists(): array
    {
        $min = $this->meta->minIndexItems();
        $entries = [['path' => '/', 'lastmod' => null]];

        foreach (Region::query()->whereNull('parent_id')->where('is_active', true)->get() as $pref) {
            $ids = $this->scope->ids($pref);
            $slug = $pref->slug;

            $events = Event::query()->where('is_published', true)->whereIn('region_id', $ids);
            if ((clone $events)->count() >= $min) {
                $entries[] = ['path' => $this->links->events($slug), 'lastmod' => null];
            }
            foreach (Category::query()->where('target', CategoryTarget::Event)->where('is_active', true)->get() as $category) {
                if ((clone $events)->where('category_id', $category->id)->count() >= $min) {
                    $entries[] = ['path' => $this->links->category($slug, $category->slug), 'lastmod' => null];
                }
            }
            if (Spot::query()->where('is_published', true)->whereIn('region_id', $ids)->count() >= $min) {
                $entries[] = ['path' => $this->links->spots($slug), 'lastmod' => null];
            }
            if (Article::query()->where('is_published', true)->whereIn('region_id', $ids)->count() >= $min) {
                $entries[] = ['path' => $this->links->articles($slug), 'lastmod' => null];
            }
        }

        return $entries;
    }

    /** @return list<array{path: string, lastmod: string|null}> */
    private function regionEntries(): array
    {
        $entries = [];
        foreach (Region::query()->where('is_active', true)->whereNotNull('intro_body')->get() as $region) {
            if ($this->regions->isIndexable($region)) {
                $entries[] = ['path' => $this->links->region($region), 'lastmod' => $region->intro_generated_at?->toDateString()];
            }
        }

        return $entries;
    }

    /**
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<T>  $query
     * @param  \Closure(T): string  $path
     * @param  list<string>  $with
     * @return list<array{path: string, lastmod: string|null}>
     */
    private function items(Builder $query, \Closure $path, array $with): array
    {
        $entries = [];
        foreach ($query->where('is_published', true)->with($with)->orderBy('id')->limit(self::LIMIT)->get() as $model) {
            $updated = $model->getAttribute('updated_at');
            $entries[] = ['path' => $path($model), 'lastmod' => $updated instanceof CarbonInterface ? $updated->toDateString() : null];
        }

        return $entries;
    }

    private function xml(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
