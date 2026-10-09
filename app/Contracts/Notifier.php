<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * 管理者への通知(Discord Webhook)。本文に個人情報・秘密の値を入れない。
 */
interface Notifier
{
    /**
     * 送れなくても例外にしない(通知の失敗で更新や定期処理を止めない)。送れたかを返す。
     */
    public function send(string $message): bool;
}
