<?php

declare(strict_types=1);

namespace App\Services\Crawl;

use App\Contracts\Notifier;
use App\Models\CrawlSource;
use App\Models\Event;

/**
 * 情報源の「信頼済み」(自動公開の対象)の管理(設計書9.6)。
 *
 * - 修正なしで承認できた件数が10件続くと、管理画面で「信頼済みにする」提案が出る(管理者が ON にする)
 * - 自動公開したイベントを管理者が直す・取り消すと、自動で OFF に戻して知らせる
 */
final class CrawlTrust
{
    public function __construct(private readonly Notifier $notifier) {}

    /** 人が、取り込んだイベントを手を加えずに承認した */
    public function approvedClean(int $crawlSourceId): void
    {
        CrawlSource::query()->whereKey($crawlSourceId)->increment('clean_approvals');
    }

    /** 却下された(連続が途切れる) */
    public function rejected(int $crawlSourceId): void
    {
        CrawlSource::query()->whereKey($crawlSourceId)->update(['clean_approvals' => 0]);
    }

    /** 管理者が、巡回から取り込んだイベントを直した・取り消した。自動公開のものなら、信頼済みを外して知らせる */
    public function eventChangedByAdmin(Event $event, string $what): void
    {
        if ($event->crawl_source_id === null) {
            return;
        }

        $source = CrawlSource::query()->find($event->crawl_source_id);
        if ($source === null) {
            return;
        }

        $source->forceFill(['clean_approvals' => 0])->save();

        if ($event->auto_published && $source->is_trusted) {
            $source->forceFill(['is_trusted' => false])->save();
            $event->forceFill(['auto_published' => false])->save();
            $this->notifier->send(__('crawl.trust_revoked', ['source' => $source->name, 'event' => $event->title, 'what' => $what]));
        }
    }
}
