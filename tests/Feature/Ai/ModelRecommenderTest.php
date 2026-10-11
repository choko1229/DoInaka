<?php

declare(strict_types=1);

use App\Enums\AiPurpose;
use App\Enums\SettingKey;
use App\Services\Ai\ModelRecommender;
use App\Services\Ai\OpenRouterModels;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\Http;

function fakeModelList(): void
{
    $now = time();
    Http::fake([OpenRouterModels::URL => Http::response(['data' => [
        ['id' => 'qwen/qwen3-235b-a22b:free', 'name' => 'Qwen3 235B', 'context_length' => 131072, 'created' => $now - 86400 * 30, 'supported_parameters' => ['response_format'], 'architecture' => ['input_modalities' => ['text']]],
        ['id' => 'meta-llama/llama-3.3-70b-instruct:free', 'name' => 'Llama 3.3 70B', 'context_length' => 65536, 'created' => $now - 86400 * 200, 'supported_parameters' => ['response_format'], 'architecture' => ['input_modalities' => ['text']]],
        ['id' => 'google/gemma-3-27b-it:free', 'name' => 'Gemma 3 27B', 'context_length' => 96000, 'created' => $now - 86400 * 100, 'supported_parameters' => ['response_format'], 'architecture' => ['input_modalities' => ['text', 'image']]],
        ['id' => 'tiny/model-1b:free', 'name' => 'Tiny 1B', 'context_length' => 8000, 'created' => $now, 'supported_parameters' => ['response_format'], 'architecture' => ['input_modalities' => ['text']]],
        ['id' => 'nojson/big-405b:free', 'name' => 'NoJson 405B', 'context_length' => 131072, 'created' => $now, 'supported_parameters' => ['temperature'], 'architecture' => ['input_modalities' => ['text']]],
        ['id' => 'openai/gpt-4o', 'name' => '有料', 'context_length' => 128000],
    ]])]);
}

it('おすすめ: 大きいモデルが先で、JSON で返せないもの・小さすぎるものは選ばれない。予備は提供元を変える', function (): void {
    fakeModelList();
    app(OpenRouterModels::class)->refresh();

    $rec = app(ModelRecommender::class)->forPurpose(AiPurpose::ReviewText);

    expect($rec[0])->toBe('qwen/qwen3-235b-a22b:free')
        ->and($rec)->toHaveCount(2)
        ->and($rec[1])->not->toStartWith('qwen/')
        ->and($rec)->not->toContain('nojson/big-405b:free')
        ->and($rec)->not->toContain('tiny/model-1b:free');
});

it('おすすめ: 画像のチェックは、画像を読めるモデルだけ', function (): void {
    fakeModelList();
    app(OpenRouterModels::class)->refresh();

    expect(app(ModelRecommender::class)->forPurpose(AiPurpose::ReviewImage))->toBe(['google/gemma-3-27b-it:free']);
});

it('おすすめ: 選んでいない用途だけ自動で入る。選んであるものは残り、入れ直しなら置き換わる', function (): void {
    fakeModelList();
    app(OpenRouterModels::class)->refresh();
    $settings = app(SettingsService::class);
    $settings->set(SettingKey::AiModelsReviewText, ['google/gemma-3-27b-it:free']);

    $filled = app(ModelRecommender::class)->apply();

    expect($filled)->toBe(count(AiPurpose::cases()) - 1)
        ->and($settings->array(SettingKey::AiModelsReviewText))->toBe(['google/gemma-3-27b-it:free'])
        ->and($settings->array(SettingKey::AiModelsRegionIntro)[0])->toBe('qwen/qwen3-235b-a22b:free');

    app(ModelRecommender::class)->apply(true);
    expect($settings->array(SettingKey::AiModelsReviewText)[0])->toBe('qwen/qwen3-235b-a22b:free');
});

it('おすすめ: 選んだモデルがすべて一覧から消えた用途には、おすすめを入れ直す', function (): void {
    fakeModelList();
    app(OpenRouterModels::class)->refresh();
    $settings = app(SettingsService::class);
    $settings->set(SettingKey::AiModelsSuggest, ['gone/old-model:free']);

    app(ModelRecommender::class)->apply();

    expect($settings->array(SettingKey::AiModelsSuggest)[0])->toBe('qwen/qwen3-235b-a22b:free');
});

it('管理画面: 一覧の取得で空の用途におすすめが入り、「おすすめで入れ直す」で全部入れ直せる', function (): void {
    fakeModelList();
    $this->actingAsVerifiedAdmin(settingsAdmin());

    $this->post('/admin/settings/ai/models/refresh')->assertRedirect()->assertSessionHas('status');
    expect(app(SettingsService::class)->array(SettingKey::AiModelsCrawl)[0])->toBe('qwen/qwen3-235b-a22b:free');

    app(SettingsService::class)->set(SettingKey::AiModelsCrawl, ['google/gemma-3-27b-it:free']);
    $this->post('/admin/settings/ai/models/recommend')->assertRedirect()->assertSessionHas('status');
    expect(app(SettingsService::class)->array(SettingKey::AiModelsCrawl)[0])->toBe('qwen/qwen3-235b-a22b:free');
});
