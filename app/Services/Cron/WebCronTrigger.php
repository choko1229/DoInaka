<?php

declare(strict_types=1);

namespace App\Services\Cron;

use App\Enums\AppMetaKey;
use App\Enums\CronMode;
use App\Services\Install\InstallEnvironment;
use App\Services\Install\InstallState;
use App\Services\Setting\AppMetaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

/**
 * アクセスをきっかけに、自分自身の内部 URL(署名つき)を、待たずに呼ぶ(WP-Cron と同じ考え方)。
 * kagoya は apache2handler なので「応答を返したあとに処理を続ける」ことができない。そのため、別のリクエストに処理を任せる。
 *  - 約1分に1回だけ(前回の実行から60秒たっているときだけ)。サイトを見ている人の表示は、待たせない(短いタイムアウトで切る)
 *  - 本物の cron が動いているとき、設定で切っているとき、設置前は、何もしない
 *  - 内部 URL は、署名・短い有効期限・1回きりの番号で守る(推測も、使い回しも、外からの呼び出しもできない)
 */
final class WebCronTrigger
{
    public const NONCE_TTL = 150;

    public const MIN_INTERVAL_SECONDS = 60;

    public function __construct(
        private readonly WebCronStatus $status,
        private readonly InstallState $install,
        private readonly AppMetaService $meta,
    ) {}

    /** 呼べたか(呼ぶ必要がなかったときは false) */
    public function fire(): bool
    {
        // 設置前は、DB がない。何も読まない
        if (InstallEnvironment::isFresh()) {
            return false;
        }
        // 1分に1回までの確認(DB を引く前の、軽い門)
        if (! Cache::add('webcron:tick', 1, self::MIN_INTERVAL_SECONDS - 5)) {
            return false;
        }
        if (! $this->install->isInstalled() || $this->status->mode() !== CronMode::Web) {
            return false;
        }
        $last = $this->status->lastWebRun();
        if ($last !== null && $last->diffInSeconds(now(), true) < self::MIN_INTERVAL_SECONDS) {
            return false;
        }

        return $this->call('schedule');
    }

    /** 長い処理(自動アップデートなど)を、別のリクエストで動かす */
    public function fireLong(): bool
    {
        return $this->call('long');
    }

    private function call(string $kind): bool
    {
        $nonce = Str::random(32);
        Cache::put('webcron:nonce:'.$nonce, true, self::NONCE_TTL);

        // 相対パスで署名し(https への転送で署名が崩れない)、行き先はメインのドメイン(APP_URL)にする
        $path = URL::temporarySignedRoute('cron.run', now()->addSeconds(120), ['k' => $kind, 'n' => $nonce], absolute: false);
        $url = rtrim(config()->string('app.url'), '/').$path;

        try {
            // 呼んだ先の処理の終わりは待たない。短いタイムアウトで切る(相手は ignore_user_abort で最後まで動く)
            Http::withOptions(['timeout' => 0.5, 'connect_timeout' => 3, 'allow_redirects' => false])
                ->withHeaders(['User-Agent' => 'DoinakaWebCron/1'])
                ->post($url);
            $this->meta->set(AppMetaKey::WebCronFailures, '0');

            return true;
        } catch (ConnectionException $e) {
            // 読み取りのタイムアウト(cURL error 28)は、待たずに切ったので、想定どおり
            if (str_contains($e->getMessage(), 'cURL error 28') || str_contains($e->getMessage(), 'timed out')) {
                $this->meta->set(AppMetaKey::WebCronFailures, '0');

                return true;
            }
            $this->failed($e);

            return false;
        } catch (Throwable $e) {
            $this->failed($e);

            return false;
        }
    }

    private function failed(Throwable $e): void
    {
        $count = (int) ($this->meta->get(AppMetaKey::WebCronFailures) ?? 0) + 1;
        $this->meta->set(AppMetaKey::WebCronFailures, (string) $count);
        // URL・署名は出さない(例外のクラスだけ)
        Log::channel('app')->warning('アクセスで動かす予約処理を呼べませんでした。', ['exception' => $e::class, 'failures' => $count]);
    }
}
