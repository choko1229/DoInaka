<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Region\RegionPageQueue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 地域ページ(紹介文の状態・再生成。設計書6.2・9.8)。一覧で複数選んで、まとめて再生成できる。
 * 準備中のページは「すぐ作る」(キューの先頭に入れる)。
 */
final class RegionPageController extends Controller
{
    public function index(Request $request): View
    {
        $filter = in_array($request->query('state'), ['none', 'pending', 'ready', 'failed'], true) ? $request->string('state')->toString() : null;
        $q = trim($request->string('q')->toString());

        $query = Region::query()->where('is_active', true)
            ->leftJoin('region_generation_queue as g', 'g.region_id', '=', 'regions.id')
            ->select('regions.*', 'g.status as queue_status', 'g.priority as queue_priority', 'g.last_error as queue_error', 'g.hits as queue_hits')
            ->when($q !== '', fn ($b) => $b->where('regions.name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%'))
            ->when($filter === 'none', fn ($b) => $b->whereNull('regions.intro_body')->where(fn ($w) => $w->whereNull('g.status')->orWhere('g.status', 'failed')))
            ->when($filter === 'pending', fn ($b) => $b->whereIn('g.status', ['pending', 'running']))
            ->when($filter === 'ready', fn ($b) => $b->whereNotNull('regions.intro_body'))
            ->when($filter === 'failed', fn ($b) => $b->where('g.status', 'failed'))
            ->orderByDesc('g.hits')->orderBy('regions.id');

        return view('admin.region-pages.index', [
            'regions' => $query->paginate(30)->withQueryString(),
            'filter' => $filter,
            'q' => $q,
            // 地域ページへの修正依頼で、審査待ちのもの(裏付けが取れず、人の判断を待っている)
            'holds' => Submission::query()->where('type', SubmissionType::Correction)->where('status', SubmissionStatus::InReview)->whereHas('corrections', fn ($q) => $q->where('target_type', 'region'))->with(['corrections', 'user'])->latest('id')->limit(20)->get(),
            'counts' => [
                'pending' => DB::table('region_generation_queue')->whereIn('status', ['pending', 'running'])->count(),
                'failed' => DB::table('region_generation_queue')->where('status', 'failed')->count(),
                'ready' => Region::query()->whereNotNull('intro_body')->count(),
            ],
        ]);
    }

    /** 選んだ地域を、キューの先頭に入れる(再生成・すぐ作る) */
    public function regenerate(Request $request, RegionPageQueue $queue, AuditLogger $audit): RedirectResponse
    {
        $ids = array_values(array_filter(array_map(fn (mixed $v): int => is_numeric($v) ? (int) $v : 0, (array) $request->input('ids', [])), fn (int $id): bool => $id > 0));
        $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200']]);

        $count = 0;
        foreach (Region::query()->whereIn('id', $ids)->get() as $region) {
            $queue->regenerate($region);
            $count++;
        }

        $user = $request->user();
        $audit->record(AuditAction::ContentUpdate, $user instanceof User ? $user : null, 'region', null, ['regenerate_intro' => $ids, 'via' => 'region_pages']);

        return back()->with('status', __('region.queued', ['count' => $count]));
    }
}
