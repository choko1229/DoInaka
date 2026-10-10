<?php

declare(strict_types=1);

use App\Enums\AiState;
use App\Enums\SettingKey;
use App\Enums\SubmissionAction;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\AiCall;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Services\Ai\AiErrorExplainer;
use App\Services\Ai\AiStatusService;
use App\Services\Ai\AiUsage;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function aiSubmission(array $attributes = []): Submission
{
    $s = new Submission;
    $s->forceFill(array_merge([
        'receipt_no' => '20261010-'.strtoupper(Str::random(6)), 'type' => SubmissionType::Spot, 'action' => SubmissionAction::Create,
        'payload' => ['title' => '棚田の展望台', 'body' => '朝は霧が出て、きれいです。'], 'status' => SubmissionStatus::InReview,
    ], $attributes))->save();

    return $s->refresh();
}

function queueJob(string $queue, ?int $reservedAt = null, ?int $availableAt = null, string $payload = '{}'): void
{
    DB::table('jobs')->insert(['queue' => $queue, 'payload' => $payload, 'attempts' => 0, 'reserved_at' => $reservedAt, 'available_at' => $availableAt ?? now()->getTimestamp(), 'created_at' => now()->getTimestamp()]);
}

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 12:00:00', 'Asia/Tokyo'));
    Cache::flush();
    $this->admin = User::factory()->admin()->twoFactor()->create();
});

afterEach(function (): void {
    Carbon::setTestNow();
    Cache::flush();
});

it('1件ごとの状態: 待ち・処理中・完了・失敗・翌日へ延期・対象外', function (): void {
    $service = app(AiStatusService::class);

    $waiting = aiSubmission(['status' => SubmissionStatus::AiPending]);
    expect($service->forSubmission($waiting)->state)->toBe(AiState::Waiting);

    // 処理中: そのジョブが、いま取り出されている
    queueJob('ai-2', now()->getTimestamp(), null, json_encode(['data' => ['command' => 'O:24:"App\\Jobs\\JudgeSubmission":1:{s:12:"submissionId";i:'.$waiting->id.';}']]));
    expect($service->forSubmission($waiting)->state)->toBe(AiState::Processing);

    $done = aiSubmission(['ai_status' => 'ok', 'ai_score' => 0.93, 'ai_result' => ['reasons' => ['写真に人が写っていない'], 'flags' => [], 'summary' => '棚田の展望台の紹介']]);
    $status = $service->forSubmission($done);
    expect($status->state)->toBe(AiState::Done)->and($status->result)->toContain(['安全スコア', '0.93'])->toContain(['理由', '写真に人が写っていない'])->and($status->isActive())->toBeFalse();

    $failed = aiSubmission(['ai_status' => 'failed', 'ai_result' => ['reason' => 'ai_failed']]);
    expect($service->forSubmission($failed)->state)->toBe(AiState::Failed)->and($service->forSubmission($failed)->error)->toContain('AI に接続できなかった');

    $deferred = aiSubmission(['status' => SubmissionStatus::AiDeferred]);
    $d = $service->forSubmission($deferred);
    expect($d->state)->toBe(AiState::Deferred)->and($d->nextTry?->format('H:i'))->toBe('00:00')->and($d->isActive())->toBeTrue();

    expect($service->forSubmission(aiSubmission(['status' => SubmissionStatus::Approved]))->state)->toBe(AiState::None);
});

it('情報提供の下書き: 作ったイベントの題名・日付・会場が、結果に出る', function (): void {
    $tip = aiSubmission(['type' => SubmissionType::Tip, 'ai_status' => 'ok', 'ai_result' => ['draft' => ['title' => '丸亀の秋祭り', 'start_date' => '2026-10-20', 'venue_name' => '丸亀神社']]]);

    expect(app(AiStatusService::class)->forSubmission($tip)->result)->toContain(['下書き(イベント)', '丸亀の秋祭り / 2026-10-20 / 丸亀神社']);
});

it('地域ページの状態: 待ち・処理中・完了(作った文とファクトチェック)・失敗(分かる言葉)・上限で延期', function (): void {
    $region = Region::factory()->create(['name' => '高松市']);
    $service = app(AiStatusService::class);

    expect($service->forRegion($region, 'pending', null)->state)->toBe(AiState::Waiting)
        ->and($service->forRegion($region, 'running', null)->state)->toBe(AiState::Processing)
        ->and($service->forRegion($region, null, null)->state)->toBe(AiState::None);

    $failed = $service->forRegion($region, 'failed', 'no_sources');
    expect($failed->state)->toBe(AiState::Failed)->and($failed->error)->toBe('裏付けにできる情報元を読めませんでした。');

    $region->forceFill(['intro_body' => '高松市は香川県の県庁所在地です。', 'intro_fact_checked' => true])->save();
    $ready = $service->forRegion($region->refresh(), null, null);
    expect($ready->state)->toBe(AiState::Done)->and($ready->result)->toContain(['作った紹介文', '高松市は香川県の県庁所在地です。']);

    app(AiUsage::class)->pauseUntil(now()->addHours(5));
    $paused = $service->forRegion(Region::factory()->create(), 'pending', null);
    expect($paused->state)->toBe(AiState::Deferred)->and($paused->nextTry)->not->toBeNull();
});

it('エラーを分かる言葉にする。URL・キー・メールアドレスは伏せ、短くする', function (): void {
    expect(AiErrorExplainer::explain('rate_limited'))->toContain('無料枠の上限')
        ->and(AiErrorExplainer::explain('invalid'))->toContain('返事の形')
        ->and(AiErrorExplainer::explain('error', 'HTTP 503 https://openrouter.ai/api sk-secret-key-123 me@example.com'))->not->toContain('openrouter.ai')->not->toContain('sk-secret')->not->toContain('me@example.com')
        ->and(AiErrorExplainer::explain('error', str_repeat('あ', 300)))->not->toContain(str_repeat('あ', 101));
});

it('ダッシュボード: 待ち・処理中・完了・失敗・翌日へ延期の件数、今日の回数と上限、モデル、最後のエラー、次に試す時刻', function (): void {
    $settings = app(SettingsService::class);
    $settings->set(SettingKey::AiEnabled, true);
    $settings->set(SettingKey::AiApiKey, 'sk-test-key');
    $settings->set(SettingKey::AiDailyLimit, 200);
    $settings->set(SettingKey::AiModelsReviewText, ['meta/llama:free', 'google/gemma:free']);

    queueJob('ai-2');
    queueJob('ai-2');
    queueJob('ai-3', now()->getTimestamp());
    queueJob('ai-4', null, now()->addHours(2)->getTimestamp());
    DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'ai-5', 'payload' => '{}', 'exception' => 'x', 'failed_at' => now()]);
    DB::table('region_generation_queue')->insert(['region_id' => Region::factory()->create()->id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
    aiSubmission(['status' => SubmissionStatus::AiDeferred]);
    foreach (['ok', 'ok', 'rate_limited'] as $status) {
        AiCall::query()->create(['purpose' => 'review_text', 'priority' => 2, 'model' => 'meta/llama:free', 'status' => $status, 'error' => $status === 'ok' ? null : 'HTTP 429', 'created_at' => now()->subMinutes(5)]);
    }
    app(AiUsage::class)->pauseUntil(now()->addHours(3));

    $summary = app(AiStatusService::class)->summary();
    expect($summary['groups']['review'])->toMatchArray(['waiting' => 2, 'processing' => 0, 'done' => 2, 'deferred' => 1])
        ->and($summary['groups']['tip']['processing'])->toBe(1)
        ->and($summary['groups']['crawl']['deferred'])->toBe(1)
        ->and($summary['groups']['region'])->toMatchArray(['waiting' => 1, 'failed' => 1])
        ->and($summary['today'])->toBe(3)->and($summary['limit'])->toBe(200)
        ->and($summary['last_error']['message'])->toContain('無料枠の上限')->and($summary['next_try']?->setTimezone('Asia/Tokyo')->format('H:i'))->toBe('15:00');

    $this->actingAsVerifiedAdmin($this->admin);
    $this->get('/admin/')->assertOk()
        ->assertSee('AI の状況')->assertSee('投稿の判定')->assertSee('情報提供の読み取り')->assertSee('巡回の解析')->assertSee('地域ページの紹介文')
        ->assertSee('翌日へ延期')->assertSee('今日(UTC)の使用回数: 3 回 / 上限 200 回')->assertSee('meta/llama:free → google/gemma:free')
        ->assertSee('最後のエラー')->assertSee('無料枠の上限に達しました')->assertSee('次に試す時刻')->assertSee('15:00');
});

it('ダッシュボード: AI を使っていないときは、そのことを出す。エラーがなければ「ありません」', function (): void {
    $this->actingAsVerifiedAdmin($this->admin);

    $this->get('/admin/')->assertOk()->assertSee('AI は使っていません')->assertSee('この7日間にエラーはありません')->assertSee('延期しているものはありません');
});

it('自動更新の印: 待ち・処理中・延期があるあいだだけ data-ai-active が付き、なければ付かない(画面が更新を止める)', function (): void {
    $this->actingAsVerifiedAdmin($this->admin);

    expect((string) $this->get('/admin/')->getContent())->not->toContain('data-ai-active');

    queueJob('ai-2');
    $html = (string) $this->get('/admin/')->getContent();
    expect($html)->toContain('id="ai-status" data-ai-live data-ai-active');
});

it('更新の仕組み: 10秒ごとに [data-ai-live] だけを差し替え、見えないタブでは動かさず、変わるものがなくなったら止まる', function (): void {
    $js = (string) file_get_contents(resource_path('js/ai-live.js'));

    expect($js)->toContain('const INTERVAL = 10000')->toContain('[data-ai-live]')->toContain('[data-ai-active]')->toContain('document.hidden')->toContain('replaceWith');
    expect((string) file_get_contents(resource_path('js/app.js')))->toContain("import './ai-live.js'");
});

it('審査の一覧と詳細: 1件ごとの AI のバッジと、判定・理由・要約が出る。処理中は自動更新の対象', function (): void {
    $this->actingAsVerifiedAdmin($this->admin);
    $done = aiSubmission(['ai_status' => 'ok', 'ai_score' => 0.93, 'ai_result' => ['reasons' => ['問題のない投稿'], 'summary' => '棚田の紹介']]);
    $pending = aiSubmission(['status' => SubmissionStatus::AiPending]);

    $list = $this->get('/admin/review')->assertOk()->assertSee('AI の状況')->assertSee('完了')->assertSee('id="ai-live-list" data-ai-live', false);
    expect((string) $list->getContent())->not->toContain('data-ai-active');

    $this->get('/admin/review?tab=waiting')->assertOk()->assertSee('待ち');
    expect((string) $this->get('/admin/review?tab=waiting')->getContent())->toContain('data-ai-active');

    $this->get('/admin/review/'.$done->id)->assertOk()->assertSee('AI の判定')->assertSee('完了')->assertSee('0.93')->assertSee('問題のない投稿')->assertSee('棚田の紹介');
    $this->get('/admin/review/'.$pending->id)->assertOk()->assertSee('順番を待っています');
    expect((string) $this->get('/admin/review/'.$pending->id)->getContent())->toContain('id="ai-live-detail" data-ai-live');
});

it('情報提供の一覧と詳細に、AI のバッジと、下書きの結果が出る', function (): void {
    $this->actingAsVerifiedAdmin($this->admin);
    $tip = aiSubmission(['type' => SubmissionType::Tip, 'payload' => ['source_url' => 'https://city.example/events/1'], 'ai_status' => 'ok', 'ai_result' => ['draft' => ['title' => '丸亀の秋祭り']]]);

    $this->get('/admin/tips')->assertOk()->assertSee('完了');
    $this->get('/admin/tips/'.$tip->id)->assertOk()->assertSee('AI の判定')->assertSee('丸亀の秋祭り');
});

it('地域ページの一覧と詳細に、AI のバッジと、作った紹介文・失敗の理由が出る', function (): void {
    $this->actingAsVerifiedAdmin($this->admin);
    $region = Region::factory()->create(['name' => '高松市']);
    DB::table('region_generation_queue')->insert(['region_id' => $region->id, 'status' => 'failed', 'last_error' => 'no_sources', 'created_at' => now(), 'updated_at' => now()]);

    $this->get('/admin/region-pages')->assertOk()->assertSee('AI の状況')->assertSee('失敗');
    $this->get('/admin/masters/regions/'.$region->id.'/edit')->assertOk()->assertSee('裏付けにできる情報元を読めませんでした');

    $region->forceFill(['intro_body' => '高松市の紹介文です。', 'intro_fact_checked' => true])->save();
    DB::table('region_generation_queue')->where('region_id', $region->id)->delete();
    $this->get('/admin/masters/regions/'.$region->id.'/edit')->assertOk()->assertSee('作った紹介文')->assertSee('高松市の紹介文です。')->assertSee('済み');
});

it('AI の状況は、管理者・編集者だけが見られる(会員・未ログインには見せない)', function (): void {
    $this->get('/admin/')->assertRedirect();
    $this->actingAs(User::factory()->create())->get('/admin/')->assertNotFound();
});
