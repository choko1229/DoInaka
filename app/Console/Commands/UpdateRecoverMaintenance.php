<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Notifier;
use App\Services\Update\UpdateLock;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\MaintenanceMode;

/**
 * 更新の途中でプロセスが止まって、メンテナンス表示だけが残ったときに解除する。
 *
 * 更新が作ったメンテナンス(reason=update)で、更新のロックが誰にも持たれておらず、
 * 一定の時間が過ぎているときだけ解除する。人が手で down にしたメンテナンスには触らない。
 */
final class UpdateRecoverMaintenance extends Command
{
    protected $signature = 'update:recover';

    protected $description = '更新の途中で残ったメンテナンス表示を解除する';

    public function handle(MaintenanceMode $maintenance, UpdateLock $lock, Notifier $notifier): int
    {
        if (! $maintenance->active()) {
            return self::SUCCESS;
        }

        $data = $maintenance->data();
        if (($data['reason'] ?? null) !== 'update' || ! is_int($data['since'] ?? null)) {
            return self::SUCCESS;
        }

        $minutes = config()->integer('update.stale_maintenance_minutes');
        if ($lock->isHeld() || (time() - $data['since']) < $minutes * 60) {
            return self::SUCCESS;
        }

        $maintenance->deactivate();
        $notifier->send(__('update.notify_recovered'));
        $this->info(__('update.recovered'));

        return self::SUCCESS;
    }
}
