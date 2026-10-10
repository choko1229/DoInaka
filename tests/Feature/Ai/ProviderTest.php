<?php

declare(strict_types=1);

use App\Enums\AiPurpose;
use App\Enums\SettingKey;
use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\InvalidSettingValueException;
use App\Services\Ai\AiRequest;
use App\Services\Ai\OpenRouterModels;
use App\Services\Ai\OpenRouterProvider;
use App\Services\Setting\SettingsService;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

require_once __DIR__.'/helpers.php';

function chatResponse(array $content, int $status = 200): Illuminate\Http\Client\PromiseInterface|PromiseInterface
{
    return Http::response(['model' => 'text/model:free', 'choices' => [['message' => ['content' => json_encode($content, JSON_UNESCAPED_UNICODE)]]], 'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 7]], $status);
}

function sampleRequest(AiPurpose $purpose = AiPurpose::ReviewText): AiRequest
{
    return new AiRequest($purpose, 'system', 'user data', ['ok' => 'bool']);
}

beforeEach(function (): void {
    app(SettingsService::class)->set(SettingKey::AiApiKey, 'sk-secret-key');
});

it('OpenRouter に、キーと JSON 指定つきで依頼し、返答を読む', function (): void {
    Http::fake([OpenRouterProvider::ENDPOINT => chatResponse(['ok' => true])]);

    $response = app(OpenRouterProvider::class)->complete(sampleRequest(), ['a/one:free', 'b/two:free']);

    expect($response->data)->toBe(['ok' => true])->and($response->promptTokens)->toBe(12)->and($response->completionTokens)->toBe(7);
    Http::assertSent(function (Request $request): bool {
        $body = $request->data();

        return $request->hasHeader('Authorization', 'Bearer sk-secret-key')
            && $body['models'] === ['a/one:free', 'b/two:free'] && $body['model'] === 'a/one:free'
            && $body['response_format'] === ['type' => 'json_object']
            && $body['provider']['data_collection'] === 'allow';
    });
});

it('削除依頼の照合(takedown_check)のリクエストには、必ず data_collection: deny が入る', function (): void {
    Http::fake([OpenRouterProvider::ENDPOINT => chatResponse(['ok' => true])]);

    app(OpenRouterProvider::class)->complete(sampleRequest(AiPurpose::TakedownCheck), ['a/one:free']);

    Http::assertSent(fn (Request $request): bool => $request->data()['provider']['data_collection'] === 'deny');
});

it('429 と 402 は「回数超過」として、リセット(UTC 0時)の時刻つきで投げる', function (): void {
    Http::fake([OpenRouterProvider::ENDPOINT => Http::sequence()->push(['error' => ['message' => 'limit']], 429)->push(['error' => ['message' => 'limit']], 402)]);

    foreach ([429, 402] as $status) {
        try {
            app(OpenRouterProvider::class)->complete(sampleRequest(), ['a/one:free']);
            $this->fail('投げられませんでした');
        } catch (AiRateLimited $e) {
            expect($e->retryAt->format('H:i:s'))->toBe('00:00:00')->and($e->retryAt->timezoneName)->toBe('UTC');
        }
    }
});

it('壊れた JSON は AiBadResponse、接続エラー・5xx は AiRequestFailed', function (): void {
    Http::fake([OpenRouterProvider::ENDPOINT => Http::sequence()
        ->push(['choices' => [['message' => ['content' => 'これは JSON ではない']]]])
        ->push('error', 503)
        ->pushFailedConnection('timeout')]);

    expect(fn () => app(OpenRouterProvider::class)->complete(sampleRequest(), ['a/one:free']))->toThrow(AiBadResponse::class);
    expect(fn () => app(OpenRouterProvider::class)->complete(sampleRequest(), ['a/one:free']))->toThrow(AiRequestFailed::class);
    expect(fn () => app(OpenRouterProvider::class)->complete(sampleRequest(), ['a/one:free']))->toThrow(AiRequestFailed::class);
});
it('コードブロックで囲まれた JSON も読める', function (): void {
    Http::fake([OpenRouterProvider::ENDPOINT => Http::response(['choices' => [['message' => ['content' => "```json\n{\"ok\": true}\n```"]]]])]);

    expect(app(OpenRouterProvider::class)->complete(sampleRequest(), ['a/one:free'])->data)->toBe(['ok' => true]);
});

it('有料モデルを設定しようとすると保存できない。無料モデルは保存できる', function (): void {
    $settings = app(SettingsService::class);

    expect(fn () => $settings->set(SettingKey::AiModelsReviewText, ['openai/gpt-4o']))->toThrow(InvalidSettingValueException::class);
    expect(fn () => $settings->set(SettingKey::AiModelsReviewText, ['meta/llama:free', 'openai/gpt-4o']))->toThrow(InvalidSettingValueException::class);
    expect(fn () => $settings->set(SettingKey::AiModelsReviewText, 'meta/llama:free'))->toThrow(InvalidSettingValueException::class);

    $settings->set(SettingKey::AiModelsReviewText, ['meta/llama:free', 'google/gemma:free']);
    expect($settings->array(SettingKey::AiModelsReviewText))->toBe(['meta/llama:free', 'google/gemma:free']);
});

it('モデルの候補の一覧に :free 以外は出ない。取得に失敗したら前の一覧のまま', function (): void {
    Http::fake([OpenRouterModels::URL => Http::response(['data' => [
        ['id' => 'meta/llama:free', 'name' => 'Llama'], ['id' => 'openai/gpt-4o', 'name' => 'GPT'], ['id' => 'google/gemma:free', 'name' => 'Gemma'],
    ]])]);

    $models = app(OpenRouterModels::class);
    expect($models->refresh())->toBe(2)
        ->and(array_keys($models->all()))->toBe(['meta/llama:free', 'google/gemma:free'])
        ->and($models->has('openai/gpt-4o'))->toBeFalse();

    // 取得に失敗した・空の一覧が返ったときは、前の一覧のまま
    Http::swap(new Factory);
    Http::fake([OpenRouterModels::URL => Http::sequence()->push('error', 500)->push(['data' => []])]);
    expect($models->refresh())->toBe(2)->and($models->refresh())->toBe(2);

    Cache::forget('ai:free-models');
});
