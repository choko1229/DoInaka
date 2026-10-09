<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Contracts\SearchEngine;
use App\Enums\CategoryTarget;
use App\Models\Article;
use App\Services\Public\SearchQueryFactory;
use App\Services\Url\UrlCanonicalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ArticleController extends PublicController
{
    public function index(Request $request, string $pref, SearchQueryFactory $factory, SearchEngine $search): View
    {
        $region = $this->pref($pref);
        $query = $factory->fromRequest($request, $region, CategoryTarget::Spot);
        $articles = $search->articles($query)->withQueryString();
        $noindex = $query->isFiltered() || $articles->total() < $this->meta->minIndexItems();
        $title = __('public.articles_title', ['region' => $region->name]);
        $crumbs = [...$this->meta->regionCrumbs($region, false), ['name' => __('public.nav_articles'), 'url' => null]];

        return view('public.articles.index', [
            'meta' => $this->meta->page($title, __('public.articles_description', ['region' => $region->name]), $this->links->articles($pref), $crumbs, $noindex),
            'pref' => $pref,
            'articles' => $articles,
            'query' => $query,
            'heading' => $title,
            'basePath' => $this->links->articles($pref),
        ]);
    }

    public function show(Request $request, string $pref, string $segment): View|RedirectResponse
    {
        $region = $this->pref($pref);
        $article = $this->resolveItem(Article::class, $region, $segment);
        if ($article instanceof RedirectResponse) {
            return $article;
        }

        $article->load(['region.parent', 'tags', 'relations']);
        $this->views->record($request, $article);

        return view('public.articles.show', [
            'meta' => $this->meta->article($article),
            'pref' => $pref,
            'article' => $article,
            'shareUrl' => app(UrlCanonicalizer::class)->shareUrl($this->links->article($article)),
        ]);
    }
}
