<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiPurpose;
use App\Enums\AiState;
use App\Enums\SettingKey;
use App\Enums\SubmissionStatus;
use App\Models\AiCall;
use App\Models\Region;
use App\Models\Submission;
use App\Services\Setting\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI の状況(管理画面)。ダッシュボードの全体のまとめと、1件ごとの状態・結果。
 * 待ち・処理中・翌日へ延期は、キュー(jobs)と、各画面の状態から。完了・失敗は、AI の呼び出しの記録(ai_calls)と失敗したジョブ(failed_jobs)から。
 */
final class AiStatusService
{
    /** キューの名前 => 表に出す名前の翻訳キー */
    public const GROUPS = ['ai-2' => 'review', 'ai-3' => 'tip', 'ai-4' => 'crawl', 'ai-5' => 'region'];

    public function __construct(private readonly AiUsage $usage, private readonly SettingsService $settings, private readonly AiClient $client) {}

    /**
     * ダッシュボードの「AI の状況」。
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $now = CarbonImmutable::now();
        $groups = [];
        foreach (self::GROUPS as $queue => $key) {
            $groups[$key] = $this->group($queue, $key, $now);
        }

        $paused = $this->usage->pausedUntil();
        $lastError = $this->lastError();

        return [
            'enabled' => $this->settings->bool(SettingKey::AiEnabled),
            'configured' => $this->client->isConfigured(),
            'groups' => $groups,
            'totals' => [
                'waiting' => array_sum(array_column($groups, 'waiting')),
                'processing' => array_sum(array_column($groups, 'processing')),
                'done' => array_sum(array_column($groups, 'done')),
                'failed' => array_sum(array_column($groups, 'failed')),
                'deferred' => array_sum(array_column($groups, 'deferred')),
            ],
            'today' => $this->usage->today(),
            'limit' => $this->settings->int(SettingKey::AiDailyLimit),
            'paused_until' => $paused,
            'models' => $this->models(),
            'last_error' => $lastError,
            'next_try' => $paused ?? $this->nextDeferred(),
        ];
    }

    /** @return array{waiting: int, processing: int, deferred: int, failed: int, done: int} */
    private function group(string $queue, string $key, CarbonImmutable $now): array
    {
        $jobs = DB::table('jobs')->where('queue', $queue);
        $waiting = (clone $jobs)->whereNull('reserved_at')->where('available_at', '<=', $now->getTimestamp())->count();
        $processing = (clone $jobs)->whereNotNull('reserved_at')->count();
        $deferred = (clone $jobs)->whereNull('reserved_at')->where('available_at', '>', $now->getTimestamp())->count();
        $failed = DB::table('failed_jobs')->where('queue', $queue)->count();

        // 地域ページの紹介文は、キューに入る前の待ち(region_generation_queue)がある。投稿は「翌日へ延期」の状態を持つ
        if ($key === 'region' && Schema::hasTable('region_generation_queue')) {
            $region = DB::table('region_generation_queue');
            $waiting += (clone $region)->where('status', 'pending')->count();
            $processing += (clone $region)->where('status', 'running')->count();
            $failed += (clone $region)->where('status', 'failed')->count();
        }
        if ($key === 'review') {
            $deferred += Submission::query()->where('status', SubmissionStatus::AiDeferred)->count();
        }

        $done = AiCall::query()->where('status', 'ok')->where('created_at', '>=', $this->todayStart())
            ->whereIn('purpose', $this->purposesOf($queue))->count();

        return ['waiting' => $waiting, 'processing' => $processing, 'deferred' => $deferred, 'failed' => $failed, 'done' => $done];
    }

    /** @return list<string> */
    private function purposesOf(string $queue): array
    {
        return array_map(fn (AiPurpose $p): string => $p->value, array_values(array_filter(AiPurpose::cases(), fn (AiPurpose $p): bool => ! $p->isSynchronous() && $p->queue() === $queue)));
    }

    private function todayStart(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC')->startOfDay()->setTimezone(config()->string('app.timezone'));
    }

    /**
     * 用途ごとに、いま使うモデル(第1候補と予備)。
     *
     * @return list<array{purpose: string, models: list<string>}>
     */
    private function models(): array
    {
        $out = [];
        foreach (AiPurpose::cases() as $purpose) {
            $models = array_values(array_filter($this->settings->array($purpose->settingKey()), 'is_string'));
            $out[] = ['purpose' => $purpose->label(), 'models' => $models];
        }

        return $out;
    }

    /** @return array{at: CarbonImmutable, purpose: string, model: string, message: string}|null */
    private function lastError(): ?array
    {
        $call = AiCall::query()->where('status', '!=', 'ok')->where('created_at', '>=', now()->subDays(7))->orderByDesc('id')->first();
        if ($call === null) {
            return null;
        }
        $purpose = AiPurpose::tryFrom($call->purpose);

        return [
            'at' => CarbonImmutable::instance($call->created_at ?? now()),
            'purpose' => $purpose?->label() ?? $call->purpose,
            'model' => (string) $call->model,
            'message' => AiErrorExplainer::explain($call->status, $call->error),
        ];
    }

    /** 延期したジョブの、いちばん早い再開の時刻 */
    private function nextDeferred(): ?CarbonImmutable
    {
        $at = DB::table('jobs')->whereIn('queue', array_keys(self::GROUPS))->whereNull('reserved_at')->where('available_at', '>', now()->getTimestamp())->min('available_at');

        return is_numeric($at) ? CarbonImmutable::createFromTimestamp((int) $at) : null;
    }

    /**
     * 投稿・情報提供1件の AI の状態と結果。
     */
    public function forSubmission(Submission $submission): AiItemStatus
    {
        $result = $this->submissionResult($submission);

        if ($submission->ai_status === 'ok') {
            return new AiItemStatus(AiState::Done, $result);
        }
        if ($submission->ai_status === 'failed') {
            $raw = is_array($submission->ai_result) ? ($submission->ai_result['reason'] ?? $submission->ai_result['error'] ?? null) : null;
            $reason = is_string($raw) ? $raw : 'unknown';

            return new AiItemStatus(AiState::Failed, $result, AiErrorExplainer::explain($reason));
        }

        return match ($submission->status) {
            SubmissionStatus::AiDeferred => new AiItemStatus(AiState::Deferred, nextTry: $this->usage->pausedUntil() ?? CarbonImmutable::now('UTC')->addDay()->startOfDay(), note: __('aistatus.note_deferred')),
            SubmissionStatus::AiPending => new AiItemStatus($this->isProcessing($submission) ? AiState::Processing : AiState::Waiting, note: $this->isProcessing($submission) ? __('aistatus.note_processing') : __('aistatus.note_waiting')),
            SubmissionStatus::Received, SubmissionStatus::Processing => new AiItemStatus(AiState::Waiting, note: __('aistatus.note_image')),
            default => new AiItemStatus(AiState::None),
        };
    }

    /** 処理中か(このジョブが、いま取り出されている) */
    private function isProcessing(Submission $submission): bool
    {
        // ジョブの payload は JSON で、中のコマンド(PHP の serialize)に submissionId が入っている
        return DB::table('jobs')->whereNotNull('reserved_at')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.data.command')) LIKE ?", ['%"submissionId";i:'.$submission->id.';%'])
            ->exists();
    }

    /** @return list<array{0: string, 1: string}> */
    private function submissionResult(Submission $submission): array
    {
        $out = [];
        $r = is_array($submission->ai_result) ? $submission->ai_result : [];

        if ($submission->ai_score !== null) {
            $out[] = [__('submission.ai_score'), (string) $submission->ai_score];
        }
        foreach ([['reasons', 'submission.ai_reasons', ' / '], ['flags', 'submission.ai_flags', ', ']] as [$key, $label, $glue]) {
            if (! empty($r[$key]) && is_array($r[$key])) {
                $out[] = [__($label), implode($glue, array_map(fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $r[$key]))];
            }
        }
        if (! empty($r['summary']) && is_string($r['summary'])) {
            $out[] = [__('submission.col_summary'), $r['summary']];
        }
        // 情報提供から作ったイベントの下書き
        $draft = $r['draft'] ?? null;
        if (is_array($draft)) {
            $parts = array_filter([$draft['title'] ?? null, $draft['start_date'] ?? null, $draft['venue_name'] ?? null], 'is_string');
            if ($parts !== []) {
                $out[] = [__('aistatus.draft'), implode(' / ', $parts)];
            }
        }

        return $out;
    }

    /**
     * 地域ページ(紹介文の生成)の状態。$queueStatus は region_generation_queue の status。
     */
    public function forRegion(Region $region, ?string $queueStatus, ?string $queueError): AiItemStatus
    {
        $result = [];
        if ($region->intro_body !== null && $region->intro_body !== '') {
            $result[] = [__('aistatus.intro'), mb_substr($region->intro_body, 0, 120)];
            $result[] = [__('aistatus.fact_check'), $region->intro_fact_checked ? __('aistatus.fact_checked') : __('aistatus.fact_unchecked')];
        }

        return match (true) {
            $queueStatus === 'running' => new AiItemStatus(AiState::Processing, $result, note: __('aistatus.note_processing')),
            $queueStatus === 'pending' && $this->usage->isPaused() => new AiItemStatus(AiState::Deferred, $result, nextTry: $this->usage->pausedUntil(), note: __('aistatus.note_deferred')),
            $queueStatus === 'pending' => new AiItemStatus(AiState::Waiting, $result, note: __('aistatus.note_waiting')),
            $queueStatus === 'failed' => new AiItemStatus(AiState::Failed, $result, AiErrorExplainer::explain($queueError ?? 'unknown', $queueError)),
            $result !== [] => new AiItemStatus(AiState::Done, $result),
            default => new AiItemStatus(AiState::None),
        };
    }
}
