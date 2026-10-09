<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Event;
use App\Models\Region;
use App\Models\Spot;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Region\RegionPageQueue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 管理者バーの部品(設計書6.5)。公開ページの HTML には入れず、表示後に読み込んで差し込む。
 * 変更する操作はすべて POST + CSRF。操作は audit_logs に残す。
 */
final class AdminBarController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function show(Request $request): View
    {
        $url = $request->query('url');
        $target = is_string($url) ? $this->target($url) : null;

        return view('admin.bar', [
            'pendingSubmissions' => DB::table('submissions')->where('status', 'in_review')->count(),
            'pendingCorrections' => DB::table('corrections')->join('submissions', 'submissions.id', '=', 'corrections.submission_id')->where('submissions.status', 'in_review')->count(),
            'aiToday' => DB::table('ai_calls')->where('created_at', '>=', now()->startOfDay())->count(),
            'target' => $target,
            'targetType' => $target === null ? null : $target->getMorphClass(),
            'user' => $request->user(),
            'returnUrl' => is_string($url) ? $url : '/',
        ]);
    }

    public function unpublish(Request $request, string $type, int $id): RedirectResponse
    {
        $model = match ($type) {
            'event' => Event::query()->findOrFail($id),
            'spot' => Spot::query()->findOrFail($id),
            'article' => Article::query()->findOrFail($id),
            default => abort(404),
        };
        $model->forceFill(['is_published' => false])->save();
        $this->audit->record(AuditAction::ContentUpdate, $this->user($request), $type, $id, ['unpublished' => true, 'via' => 'admin_bar']);

        return redirect()->to($this->back($request));
    }

    public function regenerate(Request $request, Region $region, RegionPageQueue $queue): RedirectResponse
    {
        // 紹介文を再生成する: 既存の紹介文を「再生成待ち」として、キューへ入れ直す
        DB::table('region_generation_queue')->updateOrInsert(
            ['region_id' => $region->id],
            ['status' => 'pending', 'reason' => 'admin', 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        );
        $this->audit->record(AuditAction::ContentUpdate, $this->user($request), 'region', $region->id, ['regenerate_intro' => true, 'via' => 'admin_bar']);

        return redirect()->to($this->back($request));
    }

    private function user(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }

    /** 戻り先は同じサイトのパスだけ(オープンリダイレクトを防ぐ) */
    private function back(Request $request): string
    {
        $to = $request->input('return');

        return is_string($to) && preg_match('#^/(?!/)[^\s\\\\]*$#', $to) === 1 ? $to : '/';
    }

    private function target(string $url): Event|Spot|Article|Region|null
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn (string $s): bool => $s !== ''));
        if ($segments === []) {
            return null;
        }

        if (count($segments) === 3 && in_array($segments[1], ['events', 'spots', 'articles'], true) && preg_match('/^(\d{1,12})(?:-|$)/', $segments[2], $m) === 1) {
            $id = (int) $m[1];

            return match ($segments[1]) {
                'events' => Event::query()->withTrashed()->find($id),
                'spots' => Spot::query()->withTrashed()->find($id),
                default => Article::query()->withTrashed()->find($id),
            };
        }

        if (in_array($segments[1] ?? '', ['events', 'spots', 'articles', 'map', 'series'], true) || count($segments) > 3) {
            return null;
        }

        // 地域ページ
        $region = Region::query()->whereNull('parent_id')->where('slug', $segments[0])->first();
        foreach (array_slice($segments, 1) as $slug) {
            if ($region === null) {
                return null;
            }
            $region = $region->children()->where('slug', $slug)->first();
        }

        return $region;
    }
}
