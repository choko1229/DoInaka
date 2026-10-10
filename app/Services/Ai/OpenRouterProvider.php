<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Enums\SettingKey;
use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Services\Setting\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * OpenRouter の chat/completions(設計書9.1)。キーは設定(暗号化)から読み、ログにも例外にも出さない。
 * 返答は JSON オブジェクトで求める。429 と 402 は「回数超過」として、リセット(UTC 0時)までの時刻を添えて投げる。
 */
final class OpenRouterProvider implements AiProvider
{
    public const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(private readonly SettingsService $settings) {}

    public function complete(AiRequest $request, array $models): AiResponse
    {
        $timeout = max(5, $this->settings->int(SettingKey::AiTimeoutSec));

        $content = $request->imageDataUrl === null
            ? $request->user
            : [['type' => 'text', 'text' => $request->user], ['type' => 'image_url', 'image_url' => ['url' => $request->imageDataUrl]]];

        $body = [
            'model' => $models[0] ?? '',
            'models' => $models,
            'messages' => [
                ['role' => 'system', 'content' => $request->system],
                ['role' => 'user', 'content' => $content],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0,
            // 削除依頼の照合だけは、記録・学習をしない提供元に限る(設計書9.4)
            'provider' => ['data_collection' => $request->purpose->denyDataCollection() ? 'deny' : 'allow'],
        ];

        try {
            $response = Http::withToken($this->settings->string(SettingKey::AiApiKey))
                ->withHeaders(['HTTP-Referer' => config()->string('app.url'), 'X-Title' => 'doinaka'])
                ->timeout($timeout)
                ->acceptJson()
                ->post(self::ENDPOINT, $body);
        } catch (ConnectionException) {
            throw new AiRequestFailed(__('ai.request_failed'));
        }

        if ($response->status() === 429 || $response->status() === 402) {
            throw new AiRateLimited($this->nextReset());
        }
        if (! $response->successful()) {
            throw new AiRequestFailed(__('ai.http_failed', ['status' => $response->status()]));
        }

        $text = $response->json('choices.0.message.content');
        $data = is_string($text) ? $this->decode($text) : null;
        if ($data === null) {
            throw new AiBadResponse(__('ai.bad_json'));
        }

        $model = $response->json('model');

        return new AiResponse(
            $data,
            is_string($model) ? $model : ($models[0] ?? ''),
            is_numeric($response->json('usage.prompt_tokens')) ? (int) $response->json('usage.prompt_tokens') : null,
            is_numeric($response->json('usage.completion_tokens')) ? (int) $response->json('usage.completion_tokens') : null,
        );
    }

    /** OpenRouter の日次のリセット(UTC 0時) */
    public function nextReset(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC')->addDay()->startOfDay();
    }

    /** @return array<string, mixed>|null */
    private function decode(string $text): ?array
    {
        $text = trim($text);
        // ```json … ``` で囲まれて返ることがある
        $text = (string) preg_replace('/\A```(?:json)?\s*|\s*```\z/i', '', $text);

        $data = json_decode($text, true);

        if (! is_array($data) || array_is_list($data)) {
            return null;
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
