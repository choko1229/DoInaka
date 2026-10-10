<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 個別ページの閲覧を日ごとに数える(人気スコアの元。設計書11.5)。管理者・ボットは数えない。
 */
final class PageViewRecorder
{
    public function __construct(private readonly TrafficFilter $filter) {}

    public function record(Request $request, Model $model): bool
    {
        if (! $this->filter->countsViewer($request)) {
            return false;
        }

        $type = $model->getMorphClass();
        $today = now()->toDateString();

        DB::table('page_views')->upsert(
            [['viewable_type' => $type, 'viewable_id' => $model->getKey(), 'viewed_on' => $today, 'count' => 1, 'created_at' => now(), 'updated_at' => now()]],
            ['viewable_type', 'viewable_id', 'viewed_on'],
            ['count' => DB::raw('`page_views`.`count` + 1'), 'updated_at' => now()],
        );

        return true;
    }
}
