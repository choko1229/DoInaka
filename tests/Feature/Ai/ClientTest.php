<?php

declare(strict_types=1);

use App\Contracts\AiProvider;
use App\Contracts\Notifier;
use App\Enums\AiPurpose;
use App\Enums\SettingKey;
use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\AiUnavailable;
use App\Models\AiCall;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiUsage;
use App\Services\Setting\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

require_once __DIR__.'/helpers.php';

function simpleRequest(AiPurpose $purpose = AiPurpose::ReviewText): AiRequest
{
    return new AiRequest($purpose, 'system', 'data', ['safety_score' => 'number', 'is_spam' => 'bool']);
}

afterEach(function (): void {
    Carbon::setTestNow();
    Cache::flush();
});

it('キーがない・オフのときは AiUnavailable(人の審査に回す)', function (): void {
    expect(fn () => app(AiClient::class)->run(simpleRequest()))->toThrow(AiUnavailable::class);

    useAi([['safety_score' => 1, 'is_spam' => false]]);
    app(SettingsService::class)->set(SettingKey::AiEnabled, false);
    expect(fn () => app(AiClient::class)->run(simpleRequest()))->toThrow(AiUnavailable::class);
});

it('返答が決めた形でなければ1回だけ再試行し、それでも外れたら AiBadResponse。想定外の項目は捨てる', function (): void {
    $fake = useAi([['safety_score' => 'high'], ['safety_score' => 'high']]);
    expect(fn () => app(AiClient::class)->run(simpleRequest()))->toThrow(AiBadResponse::class);
    expect($fake->requests)->toHaveCount(2);

    $fake = useAi([['safety_score' => 'high'], ['safety_score' => 0.7, 'is_spam' => false, 'extra' => 'drop me']]);
    expect(app(AiClient::class)->run(simpleRequest()))->toBe(['safety_score' => 0.7, 'is_spam' => false]);
    expect($fake->requests)->toHaveCount(2);
});

it('接続エラーは1回だけ再試行する', function (): void {
    $fake = useAi([new AiRequestFailed('boom'), ['safety_score' => 1, 'is_spam' => false]]);
    expect(app(AiClient::class)->run(simpleRequest()))->toBe(['safety_score' => 1, 'is_spam' => false]);
    expect($fake->requests)->toHaveCount(2);

    useAi([new AiRequestFailed('boom')]);
    expect(fn () => app(AiClient::class)->run(simpleRequest()))->toThrow(AiRequestFailed::class);
});

it('呼び出しごとに ai_calls に記録される(用途・優先順位・モデル・状態・プロンプトの版)', function (): void {
    useAi([['safety_score' => 1, 'is_spam' => false]]);
    app(AiClient::class)->run(new AiRequest(AiPurpose::ReviewText, 's', 'd', ['safety_score' => 'number', 'is_spam' => 'bool'], null, null, '7'));

    $call = AiCall::query()->firstOrFail();
    expect($call->purpose)->toBe('review_text')->and($call->priority)->toBe(2)->and($call->model)->toBe('text/model:free')
        ->and($call->status)->toBe('ok')->and($call->prompt_version)->toBe('7')->and($call->request_tokens)->toBe(10);
});

it('制限エラー(429)のあとは、リセットまで新しい呼び出しを止める。止まっている間は AI を呼ばない', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00', 'UTC'));
    $fake = useAi([new AiRateLimited(CarbonImmutable::parse('2026-10-13 00:00:00', 'UTC'))]);

    expect(fn () => app(AiClient::class)->run(simpleRequest()))->toThrow(AiRateLimited::class);
    expect(app(AiUsage::class)->isPaused())->toBeTrue()
        ->and(AiCall::query()->where('status', 'rate_limited')->count())->toBe(1);

    expect(fn () => app(AiClient::class)->run(simpleRequest()))->toThrow(AiRateLimited::class);
    expect($fake->requests)->toHaveCount(1);

    // リセット(UTC 0時)のあとは、また使える
    Carbon::setTestNow(Carbon::parse('2026-10-13 00:01:00', 'UTC'));
    expect(app(AiUsage::class)->isPaused())->toBeFalse();
});

it('管理者の操作(画面から同期で呼ぶもの)は、止まっていても画面からすぐ再試行できる', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00', 'UTC'));
    $fake = useAi([new AiRateLimited(CarbonImmutable::parse('2026-10-13 00:00:00', 'UTC')), ['safety_score' => 1, 'is_spam' => false]]);
    app(SettingsService::class)->set(SettingKey::AiModelsSuggest, ['text/model:free']);

    expect(fn () => app(AiClient::class)->run(simpleRequest(AiPurpose::ReviewText)))->toThrow(AiRateLimited::class);
    // 投稿の判定は止まったまま。管理者の提案は、すぐ再試行できる
    expect(fn () => app(AiClient::class)->run(simpleRequest(AiPurpose::ReviewText)))->toThrow(AiRateLimited::class);
    expect(app(AiClient::class)->run(simpleRequest(AiPurpose::Suggest)))->toBe(['safety_score' => 1, 'is_spam' => false]);
    expect($fake->requests)->toHaveCount(2);
});

it('今日の使用回数は UTC 0時で0に戻る', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 23:30:00', 'UTC'));
    useAi([['safety_score' => 1, 'is_spam' => false]]);
    app(AiClient::class)->run(simpleRequest());
    app(AiClient::class)->run(simpleRequest());

    $usage = app(AiUsage::class);
    expect($usage->today())->toBe(2);

    Carbon::setTestNow(Carbon::parse('2026-10-13 00:01:00', 'UTC'));
    expect($usage->today())->toBe(0);
    app(AiClient::class)->run(simpleRequest());
    expect($usage->today())->toBe(1);
});

it('用途の優先順位とキュー: 管理者の操作 → 判定 → 情報提供 → 巡回 → 紹介文', function (): void {
    expect(AiPurpose::Suggest->priority())->toBe(1)->and(AiPurpose::DraftFromUrl->priority())->toBe(1)
        ->and(AiPurpose::ReviewText->priority())->toBe(2)->and(AiPurpose::Tip->priority())->toBe(3)
        ->and(AiPurpose::Crawl->priority())->toBe(4)->and(AiPurpose::RegionIntro->priority())->toBe(5)
        ->and(AiPurpose::ReviewText->queue())->toBe('ai-2')->and(AiPurpose::Tip->queue())->toBe('ai-3')
        ->and(AiPurpose::Crawl->queue())->toBe('ai-4')->and(AiPurpose::RegionIntro->queue())->toBe('ai-5');

    // スケジューラのワーカーは、この順にキューを取り出す
    expect(file_get_contents(base_path('routes/console.php')))->toContain('--queue=high,ai-2,ai-3,ai-4,ai-5,low');
});

it('第1候補のモデルが一覧から消えると予備で動き、Discord の通知は1回だけ。予備もなければ使えない', function (): void {
    $notifier = new FakeNotifier;
    app()->instance(Notifier::class, $notifier);
    useAi([['safety_score' => 1, 'is_spam' => false]], ['gone/model:free', 'backup/model:free']);
    knownFreeModels(['backup/model:free', 'other/model:free']);

    $fake = app()->make(AiProvider::class);
    app(AiClient::class)->run(simpleRequest());
    app(AiClient::class)->run(simpleRequest());

    expect($fake->modelLists)->toBe([['backup/model:free'], ['backup/model:free']])
        ->and($notifier->sent)->toHaveCount(1)->and($notifier->sent[0])->toContain('gone/model:free');

    // 予備もなければ、人の審査に回る
    app(SettingsService::class)->set(SettingKey::AiModelsReviewText, ['gone/model:free', 'gone2/model:free']);
    expect(fn () => app(AiClient::class)->run(simpleRequest()))->toThrow(AiUnavailable::class);
});

it('有料モデルが(設定をすり抜けて)入っていても、使わない', function (): void {
    useAi([['safety_score' => 1, 'is_spam' => false]]);
    // 設定の検証を通さずに、DB に直接入れた場合を想定する
    DB::table('settings')->updateOrInsert(['key' => SettingKey::AiModelsReviewText->value], ['value' => json_encode(['openai/gpt-4o']), 'is_secret' => false]);
    app(SettingsService::class)->flush();

    expect(fn () => app(AiClient::class)->run(simpleRequest()))->toThrow(AiUnavailable::class);
});
