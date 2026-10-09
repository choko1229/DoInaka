<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Contracts\Notifier;
use App\Enums\AiPurpose;
use App\Enums\SettingKey;
use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\AiUnavailable;
use App\Models\AiCall;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\Cache;

/**
 * AI を使う入口(設計書9章)。キー・設定・停止・モデルの選択・ログ・返答の検証と再試行をここで行う。
 *
 * - 使えない(オフ・キー未設定・使えるモデルなし)→ AiUnavailable(呼び出し側は人の審査に回す)
 * - 制限エラー(429・402)→ リセット(UTC 0時)まで新しい呼び出しを止め、AiRateLimited(呼び出し側は翌日に回す)
 * - 返答が決めた形でなければ1回だけ再試行し、それでも外れたら AiBadResponse
 * - 接続・タイムアウトなどは1回だけ再試行する
 */
final class AiClient
{
    public function __construct(
        private readonly AiProvider $provider,
        private readonly SettingsService $settings,
        private readonly AiUsage $usage,
        private readonly OpenRouterModels $models,
        private readonly Notifier $notifier,
    ) {}

    /** AI を使える設定か(オフ・キー未設定でないか。停止中かは別) */
    public function isConfigured(): bool
    {
        return $this->settings->bool(SettingKey::AiEnabled) && $this->settings->string(SettingKey::AiApiKey) !== '';
    }

    /**
     * @return array<string, mixed> 検証済みの返答
     *
     * @throws AiUnavailable
     * @throws AiRateLimited
     * @throws AiBadResponse
     * @throws AiRequestFailed
     */
    public function run(AiRequest $request): array
    {
        if (! $this->isConfigured()) {
            throw new AiUnavailable(__('ai.unavailable'));
        }

        // 管理者の操作(画面から同期で呼ぶもの)は、止まっていても、画面からすぐ再試行できる(設計書9.3)
        $paused = $request->purpose->isSynchronous() ? null : $this->usage->pausedUntil();
        if ($paused !== null) {
            throw new AiRateLimited($paused);
        }

        $models = $this->chooseModels($request->purpose);
        if ($models === []) {
            throw new AiUnavailable(__('ai.no_model', ['purpose' => $request->purpose->label()]));
        }

        $invalid = 0;
        $failed = 0;

        while (true) {
            $started = microtime(true);
            try {
                $response = $this->provider->complete($request, $models);
            } catch (AiRateLimited $e) {
                $this->log($request, $models[0], 'rate_limited', null, null, $started, $e->getMessage());
                $this->usage->pauseUntil($e->retryAt);
                throw $e;
            } catch (AiBadResponse $e) {
                $this->log($request, $models[0], 'invalid', null, null, $started, $e->getMessage());
                if (++$invalid > 1) {
                    throw $e;
                }

                continue;
            } catch (AiRequestFailed $e) {
                $this->log($request, $models[0], 'error', null, null, $started, $e->getMessage());
                if (++$failed > 1) {
                    throw $e;
                }

                continue;
            }

            $clean = AiSchema::validate($response->data, $request->schema);
            if ($clean === null) {
                $this->log($request, $response->model ?: $models[0], 'invalid', $response->promptTokens, $response->completionTokens, $started, __('ai.schema_mismatch'));
                if (++$invalid > 1) {
                    throw new AiBadResponse(__('ai.schema_mismatch'));
                }

                continue;
            }

            $this->log($request, $response->model ?: $models[0], 'ok', $response->promptTokens, $response->completionTokens, $started, null);

            return $clean;
        }
    }

    /**
     * 設定の候補(第1候補と予備)から、いま使えるものを順に選ぶ。一覧が取れているときは、一覧にないモデルを飛ばす。
     * 第1候補が消えていたら、予備に切り替えて、Discord に1回だけ知らせる(設計書9.2)。
     *
     * @return list<string>
     */
    public function chooseModels(AiPurpose $purpose): array
    {
        $configured = array_values(array_filter($this->settings->array($purpose->settingKey()), fn (mixed $m): bool => is_string($m) && OpenRouterModels::isFree($m)));

        if (! $this->models->isKnown()) {
            return $configured;
        }

        $available = array_values(array_filter($configured, fn (string $m): bool => $this->models->has($m)));

        if ($configured !== [] && ($available === [] || $available[0] !== $configured[0])) {
            $key = 'ai:model-gone:'.$purpose->value.':'.$configured[0];
            if (Cache::add($key, true, now()->addDays(30))) {
                $this->notifier->send(__('ai.model_gone', ['purpose' => $purpose->label(), 'model' => $configured[0], 'fallback' => $available[0] ?? __('ai.none')]));
            }
        }

        return $available;
    }

    private function log(AiRequest $request, string $model, string $status, ?int $in, ?int $out, float $started, ?string $error): void
    {
        $call = new AiCall;
        $call->forceFill([
            'purpose' => $request->purpose->value,
            'priority' => $request->purpose->priority(),
            'model' => mb_substr($model, 0, 120),
            'status' => $status,
            'request_tokens' => $in,
            'response_tokens' => $out,
            'latency_ms' => (int) round((microtime(true) - $started) * 1000),
            'error' => $error === null ? null : mb_substr($error, 0, 500),
            'submission_id' => $request->submissionId,
            'prompt_version' => $request->promptVersion,
            'created_at' => now(),
        ])->save();
    }
}
