<?php

declare(strict_types=1);

namespace App\Services\Cron;

use App\Enums\AppMetaKey;
use App\Enums\CronMode;
use App\Services\Setting\AppMetaService;
use App\Services\Update\CronHealth;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * アクセスをきっかけに動かす予約処理の本体(schedule:run 相当と、キューの処理)。時間の上限つき(既定25秒)。
 *
 * - サーバーの cron と違い、PHP は Web サーバーの中(apache2handler)で動いていて、PHP_BINARY が php ではない。
 *   そのため schedule:run が行う「別のプロセスで artisan を動かす」ことができない。同じプロセスの中で、時刻になった予約を順に動かす
 * - キューの処理(queue:work)は、残りの時間を上限(--max-time)にして最後に動かす。動き始めたジョブは終わるまで走るが、上限を超えて次を取らない
 * - 自動アップデートは時間がかかるので、別のリクエスト(runLong)で動かす。PHP の実行時間の上限を延ばせないときは、動かさない
 * - 1分に1回まで、同時に1つだけ(ロック)。本物の cron が動いているとわかったら、何もしない
 */
final class WebCronRunner
{
    public const LIMIT_SECONDS = 25;

    public const QUEUES = 'high,ai-2,ai-3,ai-4,ai-5,low';

    /** schedule の中で、ここでは動かさないもの(キューは最後に上限つきで、アップデートは別のリクエストで) */
    private const SKIPPED = ['queue-work', 'update-run'];

    public function __construct(
        private readonly Application $app,
        private readonly WebCronStatus $status,
        private readonly WebCronBudget $budget,
        private readonly WebCronTrigger $trigger,
        private readonly CronHealth $health,
        private readonly AppMetaService $meta,
    ) {}

    /** @return array<string, mixed> */
    public function run(): array
    {
        if ($this->status->mode() !== CronMode::Web) {
            return ['skipped' => $this->status->mode() === CronMode::Cron ? 'cron' : 'off'];
        }

        $lock = Cache::lock('webcron:run', self::LIMIT_SECONDS + 60);
        if (! $lock->get()) {
            return ['skipped' => 'busy'];
        }

        try {
            $last = $this->status->lastWebRun();
            if ($last !== null && $last->diffInSeconds(now(), true) < WebCronTrigger::MIN_INTERVAL_SECONDS - 5) {
                return ['skipped' => 'too_soon'];
            }

            $started = microtime(true);
            $this->extendTimeLimit(self::LIMIT_SECONDS + 30);
            $this->budget->start(self::LIMIT_SECONDS);
            // 動き始めた印(同時に別のリクエストが入らないように、先に書く)
            $this->health->beat('web');

            $ran = [];
            $errors = [];
            foreach ($this->dueEvents() as $name => $event) {
                if ($this->budget->remaining() === 0) {
                    break;
                }
                try {
                    if ($this->runEvent($event)) {
                        $ran[] = (string) $name;
                        $this->markRan((string) $name);
                    }
                } catch (Throwable $e) {
                    $errors[] = $name.': '.$e::class;
                    report($e);
                }
            }

            $worked = $this->work();
            $longDue = $this->updateIsDue();

            $result = [
                'ran' => $ran,
                'queue' => $worked,
                'errors' => $errors,
                'seconds' => round(microtime(true) - $started, 1),
                'at' => now()->toIso8601String(),
            ];
            $this->meta->set(AppMetaKey::WebCronLastResult, json_encode($result, JSON_UNESCAPED_UNICODE) ?: '{}');
        } finally {
            $this->budget->stop();
            $lock->release();
        }

        if ($longDue) {
            $this->trigger->fireLong();
        }

        return $result;
    }

    /**
     * 長い処理(自動アップデート)。PHP の実行時間の上限を延ばせないときは、動かさない(途中で止まると、更新が壊れる)。
     *
     * @return array<string, mixed>
     */
    public function runLong(): array
    {
        if ($this->status->mode() !== CronMode::Web) {
            return ['skipped' => 'cron'];
        }
        if (! $this->extendTimeLimit(900)) {
            return ['skipped' => 'time_limit'];
        }

        $lock = Cache::lock('webcron:long', 960);
        if (! $lock->get()) {
            return ['skipped' => 'busy'];
        }

        try {
            $event = $this->event('update-run');
            if ($event === null || ! $event->isDue($this->app)) {
                return ['skipped' => 'not_due'];
            }
            $this->runEvent($event);

            return ['ran' => ['update-run']];
        } finally {
            $lock->release();
        }
    }

    /** PHP の実行時間の上限を $seconds にできたか(0 = 無制限も可) */
    private function extendTimeLimit(int $seconds): bool
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit($seconds);
        }
        $limit = (int) ini_get('max_execution_time');

        return $limit === 0 || $limit >= $seconds;
    }

    /**
     * 動かす予約。**時刻になったもの**に加えて、**取りこぼしたもの**(前回動かしたあとに、予定の時刻が過ぎていたもの)も動かす。
     * サーバーの cron は毎分動くので、毎日3時40分の予約は、その分に必ず動く。アクセスで動かす方式は、その分にアクセスがないと、
     * 動くべき分を飛ばしてしまう(アクセスが少ない時間帯に動かす予約=毎日・毎週のものが、ずっと動かなくなる)。それを、遅れてでも動かす。
     * 予約ごとの最後に動かした時刻を app_meta に持つ。記録のない予約は、記録を始めるだけ(一度に全部は動かさない)。
     *
     * @return array<int|string, Event>
     */
    private function dueEvents(): array
    {
        /** @var array<int|string, string> $runs */
        $runs = $this->eventRuns();
        $now = CarbonImmutable::now();
        $due = [];

        foreach ($this->schedule()->events() as $event) {
            /** @var Event $event */
            $name = (string) $event->description;
            if (in_array($name, self::SKIPPED, true)) {
                continue;
            }
            $key = $name !== '' ? $name : (string) spl_object_id($event);
            $seen = isset($runs[$key]) ? CarbonImmutable::parse($runs[$key]) : null; // 記録のない予約は null

            $isDue = $event->isDue($this->app);
            $missed = $seen !== null && $this->previousDue($event, $now)?->greaterThan($seen) === true;

            if (! $isDue && ! $missed) {
                if ($seen === null) {
                    $runs[(string) $key] = $now->toIso8601String();
                }

                continue;
            }
            $due[$key] = $event;
        }
        $this->saveEventRuns($runs);

        return $due;
    }

    /** その予約の、直近の予定の時刻(いまを含む) */
    private function previousDue(Event $event, CarbonImmutable $now): ?CarbonImmutable
    {
        try {
            $timezone = config()->string('app.timezone');
            $previous = (new CronExpression($event->expression))->getPreviousRunDate($now->setTimezone($timezone)->toDateTime(), 0, true, $timezone);

            return CarbonImmutable::instance($previous);
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<int|string, string> */
    private function eventRuns(): array
    {
        $json = $this->meta->get(AppMetaKey::WebCronEventRuns);
        $data = $json === null ? null : json_decode($json, true);

        /** @var array<int|string, string> $runs */
        $runs = is_array($data) ? $data : [];

        return $runs;
    }

    /** @param  array<int|string, string>  $runs */
    private function saveEventRuns(array $runs): void
    {
        $this->meta->set(AppMetaKey::WebCronEventRuns, json_encode($runs, JSON_UNESCAPED_UNICODE) ?: '{}');
    }

    /** 動かした予約の、最後の時刻を残す */
    private function markRan(string $key): void
    {
        $runs = $this->eventRuns();
        $runs[$key] = CarbonImmutable::now()->toIso8601String();
        $this->saveEventRuns($runs);
    }

    private function updateIsDue(): bool
    {
        $event = $this->event('update-run');

        return $event !== null && $event->isDue($this->app);
    }

    private function event(string $name): ?Event
    {
        foreach ($this->schedule()->events() as $event) {
            /** @var Event $event */
            if ($event->description === $name) {
                return $event;
            }
        }

        return null;
    }

    private function schedule(): Schedule
    {
        // HTTP のリクエストでは、予約(routes/console.php)がまだ読まれていない。コンソールのカーネルを起動して読み込む
        $this->app->make(ConsoleKernel::class)->bootstrap();

        return $this->app->make(Schedule::class);
    }

    /** 同じプロセスの中で、1つの予約を動かす。動かしたら true */
    private function runEvent(Event $event): bool
    {
        if ($event->shouldSkipDueToOverlapping()) {
            return false;
        }

        if ($event instanceof CallbackEvent) {
            $event->run($this->app);

            return true;
        }

        // コマンドの予約は、'php' 'artisan' name --option の形。artisan のあとを、同じプロセスで動かす
        if (preg_match('/artisan[\'"]?\s+(.+)$/', (string) $event->command, $m) !== 1) {
            return false;
        }
        if ($event->withoutOverlapping && ! $event->mutex->create($event)) {
            return false;
        }

        try {
            Artisan::call($m[1]);
        } finally {
            if ($event->withoutOverlapping) {
                $event->mutex->forget($event);
            }
        }

        return true;
    }

    /** キューを、残りの時間だけ処理する */
    private function work(): int
    {
        $remaining = $this->budget->remaining() ?? 0;
        if ($remaining < 3) {
            return 0;
        }

        return Artisan::call('queue:work', ['--stop-when-empty' => true, '--max-time' => $remaining - 1, '--queue' => self::QUEUES, '--tries' => 3]);
    }
}
