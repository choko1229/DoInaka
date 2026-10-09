<?php

declare(strict_types=1);

namespace App\Services\Update;

use App\Contracts\Notifier;
use App\Enums\SettingKey;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Discord の Webhook へ知らせる。URL は暗号化して settings に持ち、ログにも画面にも出さない。
 */
final class DiscordNotifier implements Notifier
{
    public function __construct(private readonly SettingsService $settings) {}

    public function send(string $message): bool
    {
        $url = $this->settings->string(SettingKey::NotifyDiscordWebhookUrl);
        if ($url === '' || preg_match('#^https://(?:[\w-]+\.)?discord(?:app)?\.com/api/webhooks/#', $url) !== 1) {
            return false;
        }

        try {
            $response = Http::timeout(10)->retry(2, 300, throw: false)->post($url, [
                'username' => config('app.name'),
                'content' => mb_substr($message, 0, 1800),
                // メンションを起こさない
                'allowed_mentions' => ['parse' => []],
            ]);

            return $response->successful();
        } catch (Throwable $e) {
            // URL が含まれうるので、例外のメッセージは出さない
            Log::channel('app')->warning('Discord への通知に失敗しました。', ['exception' => $e::class]);

            return false;
        }
    }
}
