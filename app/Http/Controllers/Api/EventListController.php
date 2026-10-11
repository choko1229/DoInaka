<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\SearchEngine;
use App\Enums\CategoryTarget;
use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Services\Public\SearchQueryFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;

/**
 * 一覧の絞り込みの部分更新。結果の HTML だけ返す(設計書7章)。条件の検証は画面と同じ SearchQueryFactory。
 */
final class EventListController extends Controller
{
    public function __invoke(Request $request, SearchQueryFactory $factory, SearchEngine $search): JsonResponse
    {
        $pref = $request->query('pref');
        $region = is_string($pref) ? Region::query()->whereNull('parent_id')->where('slug', $pref)->where('is_active', true)->first() : null;
        abort_if($region === null, 404);

        $query = $factory->fromRequest($request, $region, CategoryTarget::Event);
        $events = $search->events($query);
        if ($events instanceof AbstractPaginator) {
            $events->withPath('/'.$region->slug.'/events/')->withQueryString();
        }

        return response()->json([
            'html' => view('public.events.partials.results', ['events' => $events, 'query' => $query, 'pref' => $region->slug, 'basePath' => '/'.$region->slug.'/events/'])->render(),
            'total' => $events->total(),
        ]);
    }
}
