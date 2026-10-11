<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\Recurrence;
use App\Exceptions\AiRateLimited;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\User;
use App\Services\Ai\AiUsage;
use App\Services\Ai\DraftService;
use App\Services\Audit\AuditLogger;
use App\Services\Content\ContentService;
use App\Services\Content\RegionOptions;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * AI 下書き作成(AdminDraft。設計書6.2・9.2): URL を入れると、そのページ(1枚。robots.txt を守る)を読んで、
 * イベントの事実の項目だけを下書きにする。画面から同期で呼ぶ(管理者の操作は、制限エラーで止まっていても、すぐ再試行できる)。
 */
final class DraftController extends Controller
{
    public function index(): View
    {
        return view('admin.drafts.index', ['result' => null, 'url' => '', 'groups' => app(RegionOptions::class)->grouped(), 'aiToday' => app(AiUsage::class)->today(), 'recent' => Event::query()->where('is_published', false)->latest('id')->limit(3)->get()]);
    }

    public function read(Request $request, DraftService $drafts): View
    {
        $request->validate(['url' => ['required', 'string', 'max:500', 'url:http,https']]);
        $url = $request->string('url')->toString();

        try {
            $result = $drafts->fromUrl($url);
        } catch (AiRateLimited $e) {
            $result = ['status' => 'limited', 'reason' => __('ai.rate_limited_retry', ['time' => $e->retryAt->setTimezone('Asia/Tokyo')->format('m/d H:i')])];
        }

        return view('admin.drafts.index', ['result' => $result, 'url' => $url, 'groups' => app(RegionOptions::class)->grouped(), 'aiToday' => app(AiUsage::class)->today(), 'recent' => Event::query()->where('is_published', false)->latest('id')->limit(3)->get()]);
    }

    /** 下書きを、非公開のイベントとして保存する(情報元は、読んだページ)。公開は編集画面で、確認してから */
    public function save(Request $request, ContentService $content, AuditLogger $audit): RedirectResponse
    {
        /** @var array{title: string, region_id: int|string, start_date: string, end_date?: string|null, start_time?: string|null, end_time?: string|null, venue?: string|null, address?: string|null, fee?: string|null, source_url: string} $data */
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'venue' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:300'],
            'fee' => ['nullable', 'string', 'max:200'],
            'source_url' => ['required', 'string', 'max:500', 'url:http,https'],
        ]);

        $actor = $request->user();
        $actor = $actor instanceof User ? $actor : null;
        $regionId = (int) $data['region_id'];

        $series = EventSeries::query()->where('title', $data['title'])->where('region_id', $regionId)->first()
            ?? $content->saveSeries(null, ['title' => $data['title'], 'recurrence' => Recurrence::Yearly->value, 'region_id' => $regionId], $actor);

        $schedules = [];
        $day = CarbonImmutable::parse($data['start_date']);
        $end = CarbonImmutable::parse($data['end_date'] ?? $data['start_date']);
        for ($i = 0; $i < 31 && $day->lessThanOrEqualTo($end); $i++, $day = $day->addDay()) {
            $schedules[] = ['date' => $day->toDateString(), 'start_time' => $data['start_time'] ?? null, 'end_time' => $data['end_time'] ?? null];
        }

        $event = $content->saveEvent(null, [
            'series_id' => $series->id, 'title' => $data['title'], 'region_id' => $regionId, 'venue_name' => $data['venue'] ?? null,
            'address' => $data['address'] ?? null, 'fee' => $data['fee'] ?? null, 'is_published' => false,
        ], $schedules, [['kind' => 'url', 'url' => $data['source_url'], 'title' => (string) parse_url($data['source_url'], PHP_URL_HOST), 'checked_at' => now()->toDateString(), 'is_official' => false]], [], $actor);

        $audit->record(AuditAction::ContentCreate, $actor, 'event', $event->id, ['via' => 'ai_draft']);

        return redirect()->route('admin.events.edit', $event)->with('status', __('ai.draft_saved'));
    }
}
