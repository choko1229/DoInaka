<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Enums\SubmissionStatus;
use App\Models\Inquiry;
use App\Models\Spot;
use App\Models\Submission;
use App\Models\User;
use App\Services\Setting\SettingsService;
use App\Services\Url\PublicLinks;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/Ai/helpers.php';
require_once __DIR__.'/Submission/helpers.php';

/*
 * 通し確認(フェーズ8): 利用者の投稿 → AI の判定(モック)→ 管理者の審査 → 公開 → 検索で見つかる → 一覧・サイトマップに出る。
 * 本番に近い設定で動かす(デバッグ表示なし、Turnstile・AI・セキュリティヘッダーあり、キューは同期)。
 */
beforeEach(function (): void {
    postWorld();
    fakeDns();
    Storage::fake('local');
    Storage::fake('public');
    config(['app.debug' => false]);
    $settings = app(SettingsService::class);
    // テストは1つのトランザクションの中なので、全文インデックス(コミット後に見える)ではなく LIKE で探す
    $settings->set(SettingKey::SearchDriver, 'like');
});

afterEach(fn () => Cache::flush());

it('投稿 → AI の判定 → 審査 → 公開 → 検索で見つかる → サイトマップに載る → 削除依頼でぼかされ、検索から外れる', function (): void {
    useAi([reviewResult(['safety_score' => 0.95])]);

    // 1. 会員でない人が投稿する(同意つき)
    $this->post('/post/spot/', spotInput(['title' => '棚田の展望台', 'body' => '朝は霧が出て、きれいな棚田が見えます。']))->assertRedirect('/post/done/');
    $submission = Submission::query()->firstOrFail();
    $this->get('/post/done/')->assertOk()->assertSee($submission->receipt_no);

    // 2. AI の判定は通ったが、会員でないので人の審査に回る。まだ公開されていない
    expect($submission->status)->toBe(SubmissionStatus::InReview)->and($submission->ai_status)->toBe('ok');
    expect(Spot::query()->count())->toBe(0);
    $this->get('/kagawa/spots/?q='.urlencode('棚田'))->assertOk()->assertDontSee('棚田の展望台');

    // 3. 管理者が承認すると公開される
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());
    $this->post("/admin/review/{$submission->id}/approve")->assertRedirect('/admin/review');
    expect($submission->refresh()->status)->toBe(SubmissionStatus::Approved);
    $spot = Spot::query()->firstOrFail();
    expect($spot->is_published)->toBeTrue();
    auth()->logout();
    $this->flushSession();

    // 4. 個別ページ・検索・サイトマップで見つかる。セキュリティヘッダーも付く
    $link = app(PublicLinks::class)->spot($spot);
    $page = $this->get($link)->assertOk()->assertSee('棚田の展望台')->assertSee('朝は霧が出て')->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($page->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
    $this->get('/kagawa/spots/?q='.urlencode('棚田'))->assertOk()->assertSee('棚田の展望台');
    $this->get('/sitemap-spots.xml')->assertOk()->assertSee($link);

    // 5. 削除依頼を受けると、本文は HTML から消え、noindex になり、検索・サイトマップから外れる(消してはいない)
    Queue::fake();
    $this->post('/contact/', ['kind' => 'takedown', 'target_url' => 'http://localhost'.$link, 'right_type' => 'privacy', 'body' => '住所が分かります。', 'consent_terms' => '1', 'consent_overseas' => '1'])->assertRedirect('/contact/done/');
    $this->get($link)->assertOk()->assertDontSee('朝は霧が出て')->assertSee('noindex', false);
    $this->get('/kagawa/spots/?q='.urlencode('棚田'))->assertOk()->assertDontSee('棚田の展望台');
    $this->get('/sitemap-spots.xml')->assertOk()->assertDontSee($link);
    expect(Inquiry::query()->firstOrFail()->urgent)->toBeTrue()->and(Spot::query()->whereKey($spot->id)->exists())->toBeTrue();

    // 6. 管理者がぼかしを外して残すと、もとに戻る
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create());
    $this->post('/admin/inquiries/'.Inquiry::query()->firstOrFail()->id.'/keep')->assertRedirect();
    auth()->logout();
    $this->flushSession();
    $this->get($link)->assertOk()->assertSee('朝は霧が出て');
    $this->get('/kagawa/spots/?q='.urlencode('棚田'))->assertOk()->assertSee('棚田の展望台');
});

it('本番の設定でも、エラーの画面は詳細(スタックトレース)を出さない', function (): void {
    $this->get('/kagawa/spots/999999-xxx/')->assertNotFound()->assertDontSee('Stack trace', false)->assertDontSee('vendor/laravel', false);
});
