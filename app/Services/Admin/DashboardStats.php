<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\EventStatus;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Enums\UserStatus;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\CrawlSource;
use App\Models\Event;
use App\Models\PageViewHour;
use App\Models\Spot;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ダッシュボードの数字(設計書6.2・フェーズ7): 未処理の件数、公開の数、アクセス、情報源と生成の状態、最近の操作。
 */
final class DashboardStats
{
    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        $inReview = fn (SubmissionType ...$types) => Submission::query()->where('status', SubmissionStatus::InReview)->whereIn('type', $types)->count();

        return [
            // 人が見る投稿(スポット・記事・コメント・写真・巡回のイベント)
            'review' => $inReview(SubmissionType::Spot, SubmissionType::Article, SubmissionType::Comment, SubmissionType::VisitPhoto, SubmissionType::Event),
            'corrections' => $inReview(SubmissionType::Correction),
            'tips' => $inReview(SubmissionType::Tip),
            'ai_waiting' => Submission::query()->whereIn('status', [SubmissionStatus::AiPending, SubmissionStatus::AiDeferred])->count(),
            'needs_check' => Submission::query()->where('type', SubmissionType::Correction)->where('status', SubmissionStatus::Approved)->where('auto_decision', 'approved')->whereNull('reviewed_by')->count(),
            'events_upcoming' => Event::query()->where('is_published', true)->whereHas('schedules', fn ($q) => $q->where('date', '>=', now()->toDateString()))->count(),
            'spots' => Spot::query()->where('is_published', true)->count(),
            'articles' => Article::query()->where('is_published', true)->count(),
            'users' => User::query()->count(),
            'users_suspended' => User::query()->where('status', UserStatus::Suspended)->count(),
            'sources_paused' => CrawlSource::query()->whereNotNull('paused_at')->count(),
            'region_queue' => (int) DB::table('region_generation_queue')->whereIn('status', ['pending', 'running'])->count(),
            'views_today' => $this->views(1),
            'views_week' => $this->views(7),
        ];
    }

    /**
     * 左の並びに出す数(審査・修正依頼・却下ボックス・日程未入力の行事)。
     *
     * @return array{review: int, corrections: int, tips: int, rejected: int, undecided: int}
     */
    public function nav(): array
    {
        $inReview = fn (SubmissionType ...$types) => Submission::query()->where('status', SubmissionStatus::InReview)->whereIn('type', $types)->count();

        return [
            'review' => $inReview(SubmissionType::Spot, SubmissionType::Article, SubmissionType::Comment, SubmissionType::VisitPhoto, SubmissionType::Event),
            'corrections' => $inReview(SubmissionType::Correction),
            'tips' => $inReview(SubmissionType::Tip),
            'rejected' => Submission::query()->whereIn('status', [SubmissionStatus::Rejected, SubmissionStatus::AutoRejected])->count(),
            'undecided' => Event::query()->where('status', EventStatus::Undecided)->count(),
        ];
    }

    /**
     * ダッシュボード(今日の回覧板)の上の部分: 一番古い審査待ち、キューと失敗、審査待ちの先頭、AI が自動で決めたもの。
     *
     * @return array<string, mixed>
     */
    public function board(): array
    {
        $oldest = Submission::query()->where('status', SubmissionStatus::InReview)->oldest('id')->first();

        return [
            'oldest_days' => $oldest?->created_at !== null ? (int) $oldest->created_at->diffInDays(now()) : null,
            'queue' => (int) DB::table('jobs')->count(),
            'failed' => (int) DB::table('failed_jobs')->count(),
            'pending' => Submission::query()->with('user')->where('status', SubmissionStatus::InReview)->latest('id')->limit(4)->get(),
            'auto' => Submission::query()->whereNotNull('auto_decision')->where('updated_at', '>=', now()->subDay())->latest('id')->limit(5)->get(),
        ];
    }

    /** @return Collection<int, AuditLog> */
    public function recentOperations(int $limit = 8): Collection
    {
        return AuditLog::query()->latest('id')->limit($limit)->get();
    }

    /** 直近 N 日(日本時間の日付)の、サイト全体の閲覧数 */
    private function views(int $days): int
    {
        $from = now()->setTimezone('Asia/Tokyo')->startOfDay()->subDays($days - 1);

        return (int) PageViewHour::query()->where('hour', '>=', $from)->sum('count');
    }
}
