<?php

declare(strict_types=1);

namespace App\Services\Url;

use App\Models\Article;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\Spot;
use Illuminate\Database\Eloquent\Model;

/**
 * 公開ページの URL(パス。先頭が県のスラッグで、末尾にスラッシュがある。設計書6.1・15.1)。
 *
 * 個別ページは `{id}-{ローマ字}`。ローマ字がなければ `{id}` だけ。ローマ字が現在の値と違えば、正しい URL へ 301 で転送する。
 */
class PublicLinks
{
    /** 県のスラッグ(地域から、県までたどる) */
    public function prefSlug(Region $region): string
    {
        $node = $region;
        $guard = 0;
        while ($node->parent_id !== null && $guard++ < 5) {
            $node = $node->parent()->firstOrFail();
        }

        return $node->slug;
    }

    public function region(Region $region): string
    {
        return '/'.$region->path().'/';
    }

    public function events(string $pref): string
    {
        return "/{$pref}/events/";
    }

    public function spots(string $pref): string
    {
        return "/{$pref}/spots/";
    }

    public function articles(string $pref): string
    {
        return "/{$pref}/articles/";
    }

    public function map(string $pref): string
    {
        return "/{$pref}/map/";
    }

    public function weekend(string $pref): string
    {
        return "/{$pref}/events/weekend/";
    }

    public function category(string $pref, string $slug): string
    {
        return "/{$pref}/events/category/{$slug}/";
    }

    public function event(Event $event): string
    {
        return $this->item($this->prefSlug($event->region()->firstOrFail()), 'events', $event->id, $event->slug);
    }

    public function spot(Spot $spot): string
    {
        return $this->item($this->prefSlug($spot->region()->firstOrFail()), 'spots', $spot->id, $spot->slug);
    }

    public function article(Article $article): string
    {
        return $this->item($this->prefSlug($article->region()->firstOrFail()), 'articles', $article->id, $article->slug);
    }

    public function series(EventSeries $series): string
    {
        return $this->item($this->prefSlug($series->region()->firstOrFail()), 'series', $series->id, $series->slug);
    }

    /** どのコンテンツでも使える入口 */
    public function for(Model $model): string
    {
        return match (true) {
            $model instanceof Event => $this->event($model),
            $model instanceof Spot => $this->spot($model),
            $model instanceof Article => $this->article($model),
            $model instanceof EventSeries => $this->series($model),
            $model instanceof Region => $this->region($model),
            default => '/',
        };
    }

    private function item(string $pref, string $section, int $id, ?string $slug): string
    {
        $tail = $slug === null || $slug === '' ? (string) $id : $id.'-'.$slug;

        return "/{$pref}/{$section}/{$tail}/";
    }

    /**
     * URL の `{id}-{slug}` を分ける。形が違えば null。
     *
     * @return array{id: int, slug: string|null}|null
     */
    public function parseItem(string $segment): ?array
    {
        if (preg_match('/^(\d{1,12})(?:-([a-z0-9-]*))?$/', $segment, $m) !== 1) {
            return null;
        }

        return ['id' => (int) $m[1], 'slug' => ($m[2] ?? '') === '' ? null : $m[2]];
    }
}
