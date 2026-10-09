<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Enums\SettingKey;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\Mail;

/**
 * 管理画面の設定(mail.*)を、メール送信の設定に実行時に反映する。
 * SMTP のホストが空のときは、環境(.env。開発では Mailpit)の設定のまま。パスワードはログにも画面にも出さない。
 */
final class MailConfigurator
{
    public function __construct(private readonly SettingsService $settings) {}

    public function apply(): void
    {
        $host = $this->settings->string(SettingKey::MailSmtpHost);
        $from = $this->settings->string(SettingKey::MailFromAddress);

        if ($from !== '') {
            config(['mail.from.address' => $from]);
        }

        if ($host === '') {
            return;
        }

        $port = $this->settings->int(SettingKey::MailSmtpPort);
        $username = $this->settings->string(SettingKey::MailSmtpUsername);
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => $username === '' ? null : $username,
            'mail.mailers.smtp.password' => $username === '' ? null : $this->settings->string(SettingKey::MailSmtpPassword),
            'mail.mailers.smtp.scheme' => $port === 465 ? 'smtps' : 'smtp',
        ]);
        // すでに作られた送信設定を捨てて、新しい設定で作り直す
        Mail::purge('smtp');
    }
}
