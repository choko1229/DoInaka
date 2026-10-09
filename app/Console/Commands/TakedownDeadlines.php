<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Notifier;
use App\Enums\ConsentStatus;
use App\Models\TakedownConsent;
use Illuminate\Console\Command;

/**
 * 削除への同意の照会で、期限まで反対がなかったものを管理者に知らせる(1日1回)。知らせるのは受付番号だけ。
 * 「削除できる」状態になるだけで、削除は管理者が押したときに行う(自動では消さない)。
 */
final class TakedownDeadlines extends Command
{
    protected $signature = 'takedown:deadlines';

    protected $description = '削除への同意の照会の期限が過ぎたものを、管理者に知らせる';

    public function handle(Notifier $notifier): int
    {
        $count = 0;
        TakedownConsent::query()
            ->where('status', ConsentStatus::Pending)->where('deadline_at', '<', now())->whereNull('notified_at')
            ->whereHas('inquiry', fn ($q) => $q->whereNull('result'))
            ->with('inquiry')
            ->each(function (TakedownConsent $consent) use ($notifier, &$count): void {
                $inquiry = $consent->inquiry;
                if ($inquiry === null) {
                    return;
                }
                $notifier->send(__('inquiry.discord_deadline', ['receipt' => $inquiry->receipt_no]));
                $consent->forceFill(['notified_at' => now()])->save();
                $count++;
            });

        $this->info(__('inquiry.deadlines_notified', ['count' => $count]));

        return self::SUCCESS;
    }
}
