<?php

declare(strict_types=1);

use App\Contracts\AiProvider;
use App\Contracts\Notifier;
use App\Enums\AiPurpose;
use App\Enums\SettingKey;
use App\Models\User;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\Cache;

/**
 * AI の呼び出し口の偽物。返事(配列)か例外を順に返し、受け取った依頼を記録する。足りなくなったら最後の返事を繰り返す。
 */
final class FakeAi implements AiProvider
{
    /** @var list<AiRequest> */
    public array $requests = [];

    /** @var list<list<string>> */
    public array $modelLists = [];

    /** @param  list<array<string, mixed>|Throwable>  $script */
    public function __construct(public array $script) {}

    public function complete(AiRequest $request, array $models): AiResponse
    {
        $this->requests[] = $request;
        $this->modelLists[] = $models;
        $next = count($this->script) > 1 ? array_shift($this->script) : $this->script[0];

        if ($next instanceof Throwable) {
            throw $next;
        }

        return new AiResponse($next, $models[0] ?? '', 10, 5);
    }
}

/** 通知の偽物(送った文を覚える) */
final class FakeNotifier implements Notifier
{
    /** @var list<string> */
    public array $sent = [];

    public function send(string $message): bool
    {
        $this->sent[] = $message;

        return true;
    }
}

/** AI を使える状態にして、偽物を差し込む */
function useAi(array $script, array $textModels = ['text/model:free'], array $imageModels = []): FakeAi
{
    $settings = app(SettingsService::class);
    $settings->set(SettingKey::AiEnabled, true);
    $settings->set(SettingKey::AiApiKey, 'sk-test-key');
    foreach (AiPurpose::cases() as $purpose) {
        $settings->set($purpose->settingKey(), $purpose === AiPurpose::ReviewImage ? $imageModels : $textModels);
    }

    $fake = new FakeAi($script);
    app()->instance(AiProvider::class, $fake);

    return $fake;
}

/** @param  array<string, mixed>  $override */
function reviewResult(array $override = []): array
{
    return array_merge([
        'safety_score' => 0.95, 'is_spam' => false, 'reasons' => [], 'flags' => [], 'normalized' => [],
        'summary' => '要約', 'category_slug' => null, 'region_slug' => null, 'tags' => [], 'duplicate_of' => null, 'romaji_slug' => null,
    ], $override);
}

/** OpenRouter の無料モデルの一覧を、キャッシュに入れる */
function knownFreeModels(array $ids): void
{
    Cache::forever('ai:free-models', array_combine($ids, $ids));
}

function memberWithApproved(int $count): User
{
    return User::factory()->create(['approved_count' => $count]);
}
