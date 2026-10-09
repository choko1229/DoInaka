<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AiPurpose;
use App\Enums\AuditAction;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Http\Controllers\Controller;
use App\Models\AiCall;
use App\Models\AuditLog;
use App\Models\Submission;
use App\Models\User;
use App\Services\Admin\ErrorLogReader;
use App\Services\Audit\AuditLogger;
use App\Support\Csv;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ログ(AdminLogs。設計書6.2・13.2): 操作・審査・AI・エラー。絞り込みと CSV の書き出し。管理者だけ。
 * CSV は、式として実行される先頭の文字を無害にする(App\Support\Csv)。CSV を書き出したことも操作ログに残す。
 */
final class LogController extends Controller
{
    private const TABS = ['operations', 'reviews', 'ai', 'errors'];

    private const CSV_LIMIT = 10_000;

    public function __construct(private readonly AuditLogger $audit, private readonly ErrorLogReader $errors) {}

    public function index(Request $request, string $tab = 'operations'): View
    {
        abort_unless(in_array($tab, self::TABS, true), 404);

        if ($tab === 'errors') {
            return view('admin.logs.errors', [
                'tab' => $tab,
                'entries' => $this->errors->read($request->string('level')->toString() ?: null, $request->string('q')->toString() ?: null),
                'levels' => $this->errors->levels(),
                'level' => $request->string('level')->toString(),
                'q' => $request->string('q')->toString(),
            ]);
        }

        $paginator = $this->query($tab, $request)->paginate(50)->withQueryString();

        return view('admin.logs.index', [
            'tab' => $tab,
            'headers' => $this->headers($tab),
            'rows' => collect($paginator->items())->map(fn ($m): array => $this->row($tab, $m))->all(),
            'paginator' => $paginator,
            'filters' => $this->filters($tab, $request),
        ]);
    }

    public function csv(Request $request, string $tab): StreamedResponse
    {
        abort_unless(in_array($tab, self::TABS, true), 404);

        $actor = $request->user();
        $this->audit->record(AuditAction::LogExport, $actor instanceof User ? $actor : null, 'log', $tab);

        return response()->streamDownload(function () use ($tab, $request): void {
            echo "\xEF\xBB\xBF";
            if ($tab === 'errors') {
                echo Csv::row(['日時', 'レベル', 'メッセージ', '詳細']);
                foreach ($this->errors->read($request->string('level')->toString() ?: null, $request->string('q')->toString() ?: null, self::CSV_LIMIT) as $e) {
                    echo Csv::row([$e['at'], $e['level'], $e['message'], $e['context']]);
                }

                return;
            }

            echo Csv::row($this->headers($tab));
            $this->query($tab, $request)->limit(self::CSV_LIMIT)->each(function ($model) use ($tab): void {
                echo Csv::row($this->row($tab, $model));
            });
        }, "doinaka-log-{$tab}-".now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return Builder<AuditLog>|Builder<Submission>|Builder<AiCall> */
    private function query(string $tab, Request $request): Builder
    {
        [$from, $to] = $this->period($request);

        return match ($tab) {
            'operations' => AuditLog::query()
                ->when(AuditAction::tryFrom($request->string('action')->toString()), fn (Builder $q, AuditAction $a) => $q->where('action', $a))
                ->when($request->integer('user') > 0, fn (Builder $q) => $q->where('user_id', $request->integer('user')))
                ->when($from, fn (Builder $q, CarbonImmutable $d) => $q->where('created_at', '>=', $d))
                ->when($to, fn (Builder $q, CarbonImmutable $d) => $q->where('created_at', '<', $d->addDay()))
                ->orderByDesc('id'),
            'reviews' => Submission::query()->with('reviewer')
                ->whereIn('status', [SubmissionStatus::Approved, SubmissionStatus::Rejected, SubmissionStatus::AutoRejected])
                ->when(SubmissionType::tryFrom($request->string('type')->toString()), fn (Builder $q, SubmissionType $t) => $q->where('type', $t))
                ->when(SubmissionStatus::tryFrom($request->string('status')->toString()), fn (Builder $q, SubmissionStatus $s) => $q->where('status', $s))
                ->when($from, fn (Builder $q, CarbonImmutable $d) => $q->where('updated_at', '>=', $d))
                ->when($to, fn (Builder $q, CarbonImmutable $d) => $q->where('updated_at', '<', $d->addDay()))
                ->orderByDesc('updated_at')->orderByDesc('id'),
            default => AiCall::query()
                ->when(AiPurpose::tryFrom($request->string('purpose')->toString()), fn (Builder $q, AiPurpose $p) => $q->where('purpose', $p->value))
                ->when(in_array($request->string('status')->toString(), ['ok', 'error', 'invalid', 'rate_limited'], true), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
                ->when($from, fn (Builder $q, CarbonImmutable $d) => $q->where('created_at', '>=', $d))
                ->when($to, fn (Builder $q, CarbonImmutable $d) => $q->where('created_at', '<', $d->addDay()))
                ->orderByDesc('id'),
        };
    }

    /** @return list<string> */
    private function headers(string $tab): array
    {
        return match ($tab) {
            'operations' => ['日時', '操作', '操作した人(ID)', '対象', '詳細'],
            'reviews' => ['日時', '受付番号', '種類', '結果', '判定した人', 'AIのスコア', '自動の判定', '理由'],
            default => ['日時', '用途', 'モデル', '状態', '入力トークン', '出力トークン', '時間(ms)', 'エラー'],
        };
    }

    /** @return list<mixed> */
    private function row(string $tab, mixed $model): array
    {
        if ($model instanceof AuditLog) {
            return [$model->created_at?->format('Y-m-d H:i:s'), $model->action->value, $model->user_id, trim(($model->target_type ?? '').' '.($model->target_id ?? '')), $model->detail];
        }
        if ($model instanceof Submission) {
            return [$model->updated_at?->format('Y-m-d H:i:s'), $model->receipt_no, $model->type->label(), $model->status->label(), $this->reviewerName($model), $model->ai_score, $model->auto_decision, $model->reject_reason];
        }
        if ($model instanceof AiCall) {
            return [$model->created_at?->format('Y-m-d H:i:s'), $model->purpose, $model->model, $model->status, $model->request_tokens, $model->response_tokens, $model->latency_ms, $model->error];
        }

        return [];
    }

    private function reviewerName(Submission $submission): string
    {
        $reviewer = $submission->getRelation('reviewer');

        return $reviewer instanceof User ? $reviewer->name : ($submission->auto_decision !== null ? 'AI' : '');
    }

    /** @return array<string, mixed> */
    private function filters(string $tab, Request $request): array
    {
        return [
            'from' => $request->string('from')->toString(), 'to' => $request->string('to')->toString(),
            'action' => $request->string('action')->toString(), 'user' => $request->string('user')->toString(),
            'type' => $request->string('type')->toString(), 'status' => $request->string('status')->toString(),
            'purpose' => $request->string('purpose')->toString(),
        ];
    }

    /** @return array{CarbonImmutable|null, CarbonImmutable|null} */
    private function period(Request $request): array
    {
        $parse = function (string $key) use ($request): ?CarbonImmutable {
            $value = $request->string($key)->toString();
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
                return null;
            }

            return CarbonImmutable::parse($value, 'Asia/Tokyo')->startOfDay();
        };

        return [$parse('from'), $parse('to')];
    }
}
