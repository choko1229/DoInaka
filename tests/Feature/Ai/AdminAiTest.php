<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Exceptions\AiRateLimited;
use App\Jobs\RunCrawlSource;
use App\Models\CrawlCandidate;
use App\Models\CrawlSource;
use App\Models\Event;
use App\Models\Region;
use App\Models\Revision;
use App\Models\Spot;
use App\Models\Submission;
use App\Models\User;
use App\Services\Setting\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/../Crawl/helpers.php';

function adminUser(): User
{
    return User::factory()->admin()->twoFactor()->create();
}

beforeEach(function (): void {
    fakeDns();
    Storage::fake('local');
});

it('URLから下書き(AdminDraft): ページを読んで事実の項目だけを下書きにし、非公開のイベントとして保存できる', function (): void {
    postWorld();
    app(SettingsService::class)->set(SettingKey::CrawlMinIntervalSeconds, 0);
    fakeSite(['https://city.example/event/1' => detailHtml('丸亀の秋祭り', '10月25日 丸亀城公園で開催。無料。')]);
    $ai = useAi([['is_event' => true, 'title' => '丸亀の秋祭り', 'start_date' => '2026-10-25', 'end_date' => null, 'start_time' => '10:00', 'end_time' => '16:00', 'venue' => '丸亀城公園', 'address' => '丸亀市一番丁', 'fee' => '無料', 'organizer' => '実行委員会', 'is_cancelled' => false, 'confidence' => 0.95]]);

    $this->actingAsVerifiedAdmin(adminUser());
    $this->get('/admin/drafts')->assertOk()->assertSee('AI下書き作成');
    $html = $this->post('/admin/drafts', ['url' => 'https://city.example/event/1'])->assertOk()->getContent();
    expect($html)->toContain('丸亀城公園')->toContain('2026-10-25')->and($ai->requests[0]->purpose->value)->toBe('draft_from_url');

    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $this->post('/admin/drafts/save', [
        'title' => '丸亀の秋祭り', 'region_id' => $region->id, 'start_date' => '2026-10-25', 'start_time' => '10:00', 'end_time' => '16:00',
        'venue' => '丸亀城公園', 'address' => '丸亀市一番丁', 'fee' => '無料', 'source_url' => 'https://city.example/event/1',
    ])->assertRedirect();

    $event = Event::query()->firstOrFail();
    expect($event->is_published)->toBeFalse()->and($event->sources()->firstOrFail()->url)->toBe('https://city.example/event/1')
        ->and($event->schedules()->count())->toBe(1);
});

it('URLから下書き: robots.txt が禁止するページは読まない。イベントでなければそう伝える', function (): void {
    postWorld();
    app(SettingsService::class)->set(SettingKey::CrawlMinIntervalSeconds, 0);
    fakeSite(['https://city.example/private/1' => detailHtml('X'), 'https://city.example/news' => detailHtml('お知らせ')], "User-agent: *\nDisallow: /private/\n");
    useAi([['is_event' => false, 'title' => null, 'start_date' => null, 'confidence' => 0.1]]);

    $this->actingAsVerifiedAdmin(adminUser());
    $this->post('/admin/drafts', ['url' => 'https://city.example/private/1'])->assertOk()->assertSee('robots.txt が読み取りを禁止している');
    $this->post('/admin/drafts', ['url' => 'https://city.example/news'])->assertOk()->assertSee('イベントの案内として読み取れませんでした');
    $this->post('/admin/drafts', ['url' => 'ftp://example.com/x'])->assertSessionHasErrors('url');
    $this->post('/admin/drafts', ['url' => 'http://127.0.0.1/admin'])->assertOk()->assertSee('読み込めませんでした');
});

it('制限エラーで止まっていても、管理者の操作は画面からすぐ再試行できる(止まっている表示つき)', function (): void {
    postWorld();
    app(SettingsService::class)->set(SettingKey::CrawlMinIntervalSeconds, 0);
    fakeSite(['https://city.example/event/1' => detailHtml('祭り')]);
    useAi([new AiRateLimited(CarbonImmutable::now('UTC')->addDay()->startOfDay()), ['is_event' => true, 'title' => '祭り', 'start_date' => '2026-10-25', 'confidence' => 0.9]]);

    $this->actingAsVerifiedAdmin(adminUser());
    $this->post('/admin/drafts', ['url' => 'https://city.example/event/1'])->assertOk()->assertSee('回数制限')->assertSee('もう一度試す');
    $this->post('/admin/drafts', ['url' => 'https://city.example/event/1'])->assertOk()->assertSee('読み取った内容');
    // ダッシュボードには、止まっていたことと、今日の回数が出る
    $this->get('/admin')->assertOk()->assertSee('今日(UTC)の使用回数');
});

it('AIに提案させる: 整形・タグ・ローマ字を返すだけ(保存しない)。不正なローマ字は捨てる', function (): void {
    useAi([['normalized' => ['title' => '棚田の展望台', 'body' => '整えた本文', 'evil' => 'x'], 'tags' => ['棚田', '展望', 3], 'romaji_slug' => 'tanada-tenboudai']]);

    $this->actingAsVerifiedAdmin(adminUser());
    $this->postJson('/admin/suggest', ['title' => '棚田の 展望台', 'body' => '本文'])->assertOk()->assertJson(['data' => ['normalized' => ['title' => '棚田の展望台', 'body' => '整えた本文'], 'tags' => ['棚田', '展望'], 'romaji_slug' => 'tanada-tenboudai']]);
    expect(Spot::query()->count())->toBe(0);

    useAi([['normalized' => [], 'romaji_slug' => 'Bad Slug!']]);
    $this->postJson('/admin/suggest', ['title' => 'x'])->assertOk()->assertJsonPath('data.romaji_slug', null);
    $this->postJson('/admin/suggest', [])->assertStatus(422);

    useAi([new AiRateLimited(CarbonImmutable::now('UTC')->addDay()->startOfDay())]);
    $this->postJson('/admin/suggest', ['title' => 'x'])->assertStatus(429);
    $this->actingAs(User::factory()->create())->postJson('/admin/suggest', ['title' => 'x'])->assertNotFound();
});

it('情報源の巡回の管理画面: 登録(危険なURLは断る)・一時停止と再開・信頼済みの切り替え・今すぐ巡回・候補の登録と無視', function (): void {
    Queue::fake();
    $world = postWorld();
    $this->actingAsVerifiedAdmin(adminUser());

    $this->get('/admin/sources')->assertOk();
    $this->post('/admin/sources', ['name' => '社内', 'url' => 'http://127.0.0.1/', 'kind' => 'web', 'region_id' => $world['marugame']->id, 'is_active' => 1])->assertStatus(422);
    $this->post('/admin/sources', ['name' => '丸亀市', 'url' => 'https://city.example/events/', 'kind' => 'web', 'region_id' => $world['marugame']->id, 'is_active' => 1])->assertRedirect(route('admin.sources'));
    $source = CrawlSource::query()->firstOrFail();
    expect($source->host)->toBe('city.example')->and($source->is_trusted)->toBeFalse();

    $this->post("/admin/sources/{$source->id}/run")->assertRedirect();
    Queue::assertPushedOn('ai-4', RunCrawlSource::class);

    $this->post("/admin/sources/{$source->id}/trust")->assertRedirect();
    expect($source->refresh()->is_trusted)->toBeTrue();
    $this->post("/admin/sources/{$source->id}/pause")->assertRedirect();
    expect($source->refresh()->isPaused())->toBeTrue()->and($source->is_trusted)->toBeFalse();
    $this->post("/admin/sources/{$source->id}/trust")->assertRedirect()->assertSessionHas('error');
    $this->post("/admin/sources/{$source->id}/resume")->assertRedirect();
    expect($source->refresh()->isPaused())->toBeFalse()->and($source->failure_streak)->toBe(0);

    $candidate = CrawlCandidate::query()->create(['url' => 'https://other.example/a', 'host' => 'other.example', 'origin' => 'tip', 'status' => 'new']);
    $this->get('/admin/sources/create?candidate='.$candidate->id)->assertOk()->assertSee('https://other.example/a');
    $this->post('/admin/sources', ['candidate' => $candidate->id, 'name' => '別', 'url' => 'https://other.example/a', 'kind' => 'rss', 'region_id' => $world['marugame']->id, 'is_active' => 1])->assertRedirect();
    expect($candidate->refresh()->status)->toBe('registered');
    $ignored = CrawlCandidate::query()->create(['url' => 'https://spam.example/', 'host' => 'spam.example', 'origin' => 'tip', 'status' => 'new']);
    $this->post("/admin/sources/candidates/{$ignored->id}/ignore")->assertRedirect();
    expect($ignored->refresh()->status)->toBe('ignored');

    // 編集者は入れない(管理者だけ)
    $this->actingAsVerifiedAdmin(User::factory()->twoFactor()->create(['role' => 'editor']))->get('/admin/sources')->assertForbidden();
});

it('AIが自動で反映した修正は「要確認」に並び、確認するか元に戻せる(戻したことも履歴に残る)', function (): void {
    postWorld();
    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $spot = Spot::factory()->create(['region_id' => $region->id, 'address' => '旧住所', 'is_published' => true, 'published_at' => now()]);
    $member = memberWithApproved(7);

    useAi([reviewResult(['safety_score' => 0.99])]);
    test()->actingAs($member)->post("/report/spot/{$spot->id}/", ['consent_terms' => '1', 'field' => 'address', 'proposed_value' => '新住所', 'source_url' => 'https://example.com/a'])->assertRedirect('/post/done/');
    $auto = Submission::query()->latest('id')->firstOrFail();
    expect($spot->refresh()->address)->toBe('新住所');

    $this->actingAsVerifiedAdmin(adminUser());
    $this->get('/admin/corrections')->assertOk()->assertSee('要確認')->assertSee($auto->receipt_no);

    $this->post("/admin/corrections/{$auto->id}/rollback")->assertRedirect();
    expect($spot->refresh()->address)->toBe('旧住所');
    $last = Revision::query()->where('revisionable_type', 'spot')->where('revisionable_id', $spot->id)->latest('id')->firstOrFail();
    expect($last->cause->value)->toBe('rollback');
    // 確認済みになったので、一覧から消え、もう戻せない
    $this->get('/admin/corrections')->assertDontSee($auto->receipt_no);
    $this->post("/admin/corrections/{$auto->id}/rollback")->assertNotFound();
});

it('審査の詳細に、AI の判定(スコア・理由・注意フラグ)が出る', function (): void {
    postWorld();
    useAi([reviewResult(['safety_score' => 0.4, 'reasons' => ['宣伝の疑い'], 'flags' => ['address'], 'summary' => '要約です'])]);
    test()->post('/post/spot/', spotInput(['title' => '怪しいスポット']))->assertRedirect('/post/done/');
    $submission = Submission::query()->firstOrFail();

    $this->actingAsVerifiedAdmin(adminUser())->get("/admin/review/{$submission->id}")->assertOk()->assertSee('宣伝の疑い')->assertSee('address')->assertSee('0.4');
});
