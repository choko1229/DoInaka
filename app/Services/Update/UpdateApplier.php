<?php

declare(strict_types=1);

namespace App\Services\Update;

use App\Contracts\CommandRunner;
use App\Contracts\Notifier;
use App\Contracts\ReleaseDownloader;
use App\Enums\UpdateStatus;
use App\Enums\UpdateTrigger;
use App\Models\UpdateRun;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Throwable;

/**
 * 更新の適用(設計書1章・実装指示書フェーズ1)。
 *
 * ロック → バックアップ(DBとコード) → ダウンロードと照合 → メンテナンス表示 → ディレクトリの入れ替え
 * → マイグレーション → 動作確認 → メンテナンス解除。
 * 入れ替えのあとで失敗したら、コードとDBを更新前に戻し、メンテナンスを解除する。
 * 戻すのにも失敗したときだけ、メンテナンス表示のまま止めて通知する(人の対応が要る)。
 *
 * 入れ替えのあとの処理は、新しい版の artisan を別プロセスで動かす(古いコードと新しいコードが混ざらないように)。
 */
class UpdateApplier
{
    /** @var list<string> */
    private array $log = [];

    public function __construct(
        private readonly CurrentVersion $current,
        private readonly ReleaseDownloader $downloader,
        private readonly ReleaseZip $zip,
        private readonly BackupStore $backups,
        private readonly SqlDumper $dumper,
        private readonly DirectorySwapper $swapper,
        private readonly UpdateLock $lock,
        private readonly CommandRunner $commands,
        private readonly Notifier $notifier,
        private readonly MaintenanceMode $maintenance,
        private readonly Connection $db,
        private readonly string $appPath,
        private readonly string $tmpPath,
    ) {}

    public function apply(ReleaseInfo $release, UpdateTrigger $trigger, ?string $triggeredBy = null): UpdateRun
    {
        $this->log = [];
        $from = $this->current->get();

        $run = UpdateRun::query()->create([
            'version_from' => $from?->__toString() ?? 'dev',
            'version_to' => $release->version->__toString(),
            'is_beta' => $release->prerelease,
            'trigger' => $trigger,
            'triggered_by' => $triggeredBy,
            'status' => UpdateStatus::Running,
            'started_at' => now(),
        ]);

        if ($from === null) {
            return $this->finish($run, UpdateStatus::Failed, __('update.dev_environment'));
        }
        if (! $release->version->isNewerThan($from)) {
            return $this->finish($run, UpdateStatus::Failed, __('update.not_newer'));
        }
        if (! $this->lock->acquire()) {
            return $this->finish($run, UpdateStatus::Failed, __('update.already_running'));
        }

        $maintenanceOn = false;
        $swapped = null;
        $zipPath = rtrim($this->tmpPath, '/').'/'.$release->assetName;
        $incoming = null;
        $backupPath = null;

        try {
            $this->step('update.step_backup');
            $backupPath = $this->backups->create($this->db, $this->appPath, $from->__toString());

            $this->step('update.step_download');
            File::ensureDirectoryExists($this->tmpPath);
            $this->downloader->download($release->assetUrl, $zipPath);
            $this->zip->verifyChecksum($zipPath, $release->sha256);
            $this->zip->inspect($zipPath, $release->version);

            $stamp = date('YmdHis');
            $incoming = $this->appPath.'-new-'.$stamp;
            $this->zip->extract($zipPath, $incoming);

            $this->step('update.step_maintenance');
            $this->maintenance->activate(['retry' => 60, 'status' => 503, 'reason' => 'update', 'since' => time()]);
            $maintenanceOn = true;

            $this->step('update.step_swap');
            $swapped = $this->swapper->swap($this->appPath, $incoming, $stamp);
            $incoming = null;

            try {
                $this->postSwap();
            } catch (Throwable $e) {
                return $this->rollback($run, $swapped, $backupPath, $e);
            }

            $this->step('update.step_publish');
            $this->resetOpcache();
            $this->maintenance->deactivate();
            $maintenanceOn = false;
            $this->swapper->remove($swapped);

            return $this->finish($run, UpdateStatus::Success, null);
        } catch (Throwable $e) {
            // 入れ替える前の失敗。サイトは元の版のまま
            if ($incoming !== null) {
                $this->swapper->remove($incoming);
            }
            if ($maintenanceOn) {
                $this->maintenance->deactivate();
            }

            return $this->finish($run, UpdateStatus::Failed, $e->getMessage());
        } finally {
            @unlink($zipPath);
            $this->lock->release();
        }
    }

    /**
     * 入れ替えのあとの処理(新しい版の artisan を使う)。失敗したら例外。
     */
    private function postSwap(): void
    {
        $this->step('update.step_migrate');
        $this->artisan(['migrate', '--force', '--no-interaction']);
        $this->artisan(['optimize:clear', '--no-interaction']);

        $this->step('update.step_health');
        $this->artisan(['update:health-check']);
    }

    /**
     * @param  list<string>  $arguments
     */
    private function artisan(array $arguments): void
    {
        $php = config('update.php_binary');
        $php = is_string($php) && $php !== '' ? $php : (new PhpExecutableFinder)->find();
        if ($php === false) {
            throw new RuntimeException(__('update.no_php'));
        }

        $result = $this->commands->run([$php, '-d', 'memory_limit=512M', 'artisan', ...$arguments], $this->appPath, 600);
        $this->log[] = '$ artisan '.$arguments[0].' → '.$result['exitCode'];

        if ($result['exitCode'] !== 0) {
            $this->log[] = mb_substr(trim($result['output']), -600);

            throw new RuntimeException(__('update.command_failed', ['command' => $arguments[0], 'detail' => $this->lastLine($result['output'])]));
        }
    }

    private function rollback(UpdateRun $run, string $old, string $backupPath, Throwable $cause): UpdateRun
    {
        $this->log[] = __('update.failed_after_swap', ['reason' => $cause->getMessage()]);
        $this->step('update.step_rollback');

        try {
            $this->swapper->rollback($this->appPath, $old);
            $this->dumper->restore($this->db, $backupPath.'/db.sql');
            $this->resetOpcache();
            $this->maintenance->deactivate();

            return $this->finish($run, UpdateStatus::RolledBack, $cause->getMessage());
        } catch (Throwable $e) {
            // 戻せなかった。メンテナンス表示のまま止め、人に知らせる
            Log::channel('app')->critical('更新を戻せませんでした。', ['reason' => $e->getMessage()]);

            return $this->finish($run, UpdateStatus::RollbackFailed, $cause->getMessage().' / '.$e->getMessage());
        }
    }

    private function finish(UpdateRun $run, UpdateStatus $status, ?string $reason): UpdateRun
    {
        if ($reason !== null) {
            $this->log[] = $reason;
        }

        // 戻したあとは DB が更新前の状態に戻っているので、記録の行を探し直して更新する
        $fresh = UpdateRun::query()->find($run->id);
        if ($fresh === null) {
            $fresh = UpdateRun::query()->create($run->only(['version_from', 'version_to', 'is_beta', 'trigger', 'triggered_by', 'started_at']) + ['status' => $status]);
        }
        $fresh->forceFill([
            'status' => $status,
            'log' => implode("\n", $this->log),
            'finished_at' => now(),
        ])->save();

        $this->notifier->send($this->message($fresh));

        return $fresh;
    }

    private function message(UpdateRun $run): string
    {
        $trigger = __('update.trigger_'.$run->trigger->value);

        return match ($run->status) {
            UpdateStatus::Success => __('update.notify_success', ['from' => $run->version_from, 'to' => $run->version_to, 'trigger' => $trigger]),
            UpdateStatus::RolledBack => __('update.notify_rolled_back', ['from' => $run->version_from, 'to' => $run->version_to, 'trigger' => $trigger]),
            UpdateStatus::RollbackFailed => __('update.notify_rollback_failed', ['from' => $run->version_from, 'to' => $run->version_to]),
            default => __('update.notify_failed', ['from' => $run->version_from, 'to' => $run->version_to, 'trigger' => $trigger]),
        };
    }

    private function step(string $key): void
    {
        $this->log[] = '['.now()->format('H:i:s').'] '.__($key);
    }

    private function resetOpcache(): void
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    private function lastLine(string $output): string
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $output))));

        return mb_substr($lines === [] ? '' : $lines[array_key_last($lines)], 0, 200);
    }
}
