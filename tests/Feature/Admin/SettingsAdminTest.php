<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\SettingKey;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Setting\SettingsCatalog;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\Cache;

function settingsAdmin(): User
{
    return User::factory()->admin()->twoFactor()->create();
}

function settingsLogs(): array
{
    return AuditLog::query()->where('action', AuditAction::SettingsChange->value)->get()->map(fn (AuditLog $l): array => ['key' => $l->target_id, 'detail' => $l->detail])->all();
}

afterEach(function (): void {
    Cache::flush();
});

it('設定の画面は管理者だけ。全タブが開ける', function (): void {
    $this->get('/admin/settings')->assertRedirect('/admin/login');
    $this->actingAs(User::factory()->create())->get('/admin/settings')->assertNotFound();
    $this->actingAsVerifiedAdmin(User::factory()->twoFactor()->create(['role' => 'editor']))->get('/admin/settings')->assertForbidden();

    $this->actingAsVerifiedAdmin(settingsAdmin());
    foreach (array_keys(SettingsCatalog::tabs()) as $tab) {
        $this->get("/admin/settings/{$tab}")->assertOk()->assertSee('設定');
    }
    $this->get('/admin/settings/nothing')->assertNotFound();
});

it('値を変えて保存でき、変更前後が操作ログに残る。変えなければ何も残らない', function (): void {
    $admin = settingsAdmin();
    $this->actingAsVerifiedAdmin($admin);

    $this->post('/admin/settings/site', ['site__name' => '新しいサイト名', 'site__description' => '説明です', 'site__operator' => '', 'site__share_host' => 'do-inaka.net'])->assertRedirect('/admin/settings/site');

    $settings = app(SettingsService::class);
    expect($settings->string(SettingKey::SiteName))->toBe('新しいサイト名')->and($settings->string(SettingKey::SiteDescription))->toBe('説明です');
    $logs = collect(settingsLogs());
    expect($logs->pluck('key')->sort()->values()->all())->toBe(['site.description', 'site.name'])
        ->and($logs->firstWhere('key', 'site.name')['detail'])->toEqual(['before' => 'ド田舎.net', 'after' => '新しいサイト名']);

    // 変えずに保存 → ログは増えない
    $this->post('/admin/settings/site', ['site__name' => '新しいサイト名', 'site__description' => '説明です', 'site__operator' => '', 'site__share_host' => 'do-inaka.net'])->assertRedirect()->assertSessionHas('status', '変更はありませんでした。');
    expect(settingsLogs())->toHaveCount(2);
});

it('1つでも誤りがあれば、何も保存しない(理由を出す)', function (): void {
    $this->actingAsVerifiedAdmin(settingsAdmin());
    $before = app(SettingsService::class)->int(SettingKey::UploadMaxMb);

    $response = $this->post('/admin/settings/posts', [
        'spam__post_per_hour' => '9', 'spam__max_urls' => '3', 'upload__max_mb' => '999', 'upload__max_files_article' => '10', 'upload__max_files_other' => 'abc',
        'upload__original_retention_days' => '60', 'tip__original_retention_days' => '60', 'privacy__ip_hash_retention_days' => '90', 'comment__auto_hide_reports' => '3',
        'turnstile__site_key' => '', 'turnstile__secret_key' => '',
    ]);

    $response->assertSessionHasErrors(['upload.max_mb', 'upload.max_files_other']);
    $settings = app(SettingsService::class);
    expect($settings->int(SettingKey::UploadMaxMb))->toBe($before)->and($settings->int(SettingKey::SpamPostPerHour))->toBe(5)->and(settingsLogs())->toBe([]);
});

it('秘密の値は、画面に末尾4文字だけ出し、空のまま保存すると変えず、「消す」で消える。操作ログには値も末尾も残らない', function (): void {
    $this->actingAsVerifiedAdmin(settingsAdmin());
    $settings = app(SettingsService::class);

    $this->post('/admin/settings/ai', aiTabPayload(['ai__api_key' => 'sk-or-v1-abcdefghijklmnop-WXYZ']))->assertRedirect();
    expect($settings->string(SettingKey::AiApiKey))->toBe('sk-or-v1-abcdefghijklmnop-WXYZ');
    // DB には暗号化されて入っている
    expect(Setting::query()->where('key', 'ai.api_key')->value('value'))->not->toContain('abcdefghijklmnop');

    $html = $this->get('/admin/settings/ai')->assertOk()->getContent();
    expect($html)->toContain('********WXYZ')->not->toContain('abcdefghijklmnop')->not->toContain('sk-or-v1');

    // 空のまま保存 → 変えない
    $this->post('/admin/settings/ai', aiTabPayload(['ai__api_key' => '']))->assertRedirect();
    expect($settings->string(SettingKey::AiApiKey))->toBe('sk-or-v1-abcdefghijklmnop-WXYZ');

    // 消す
    $this->post('/admin/settings/ai', aiTabPayload(['clear' => ['ai__api_key' => '1']]))->assertRedirect();
    expect($settings->string(SettingKey::AiApiKey))->toBe('');

    $dump = json_encode(AuditLog::query()->get()->map->getAttributes()->all(), JSON_UNESCAPED_UNICODE);
    expect($dump)->not->toContain('abcdefghijklmnop')->not->toContain('WXYZ');
    $keyLog = collect(settingsLogs())->where('key', 'ai.api_key')->first();
    expect($keyLog['detail']['after'])->toBe('********');
});

/** AI のタブの、変えない項目を含む入力 */
function aiTabPayload(array $override = []): array
{
    return array_merge([
        'ai__enabled' => '1', 'ai__timeout_sec' => '30', 'ai__daily_limit' => '',
        'review__auto_approve_min_approved' => '5', 'review__auto_approve_min_score' => '0.9', 'review__auto_reject_max_score' => '0.05',
        'review__auto_reject_shadow_days' => '14', 'review__image_fallback_min_score' => '0.95', 'review__rejected_retention_days' => '90',
    ], $override);
}

it('AI のモデル: 無料(:free)だけ保存できる。有料は断られ、一覧にない無料モデルも保存できる(候補の一覧は取得したものだけ)', function (): void {
    Cache::forever('ai:free-models', ['meta/llama:free' => 'Llama', 'google/gemma:free' => 'Gemma']);
    $this->actingAsVerifiedAdmin(settingsAdmin());
    $html = $this->get('/admin/settings/ai')->assertOk()->getContent();
    expect($html)->toContain('meta/llama:free')->and($html)->not->toContain('openai/gpt-4o');

    $this->post('/admin/settings/ai', aiTabPayload(['models' => ['review_text' => ['openai/gpt-4o', 'meta/llama:free']]]))->assertSessionHasErrors(['ai.models.review_text']);
    expect(app(SettingsService::class)->array(SettingKey::AiModelsReviewText))->toBe([]);

    $this->post('/admin/settings/ai', aiTabPayload(['models' => ['review_text' => ['meta/llama:free', 'google/gemma:free', ''], 'suggest' => ['google/gemma:free']]]))->assertRedirect();
    $settings = app(SettingsService::class);
    expect($settings->array(SettingKey::AiModelsReviewText))->toBe(['meta/llama:free', 'google/gemma:free'])->and($settings->array(SettingKey::AiModelsSuggest))->toBe(['google/gemma:free']);
    expect(collect(settingsLogs())->pluck('key')->all())->toContain('ai.models.review_text');
});

it('真偽・小数・重み・空にできる設定を保存できる', function (): void {
    $this->actingAsVerifiedAdmin(settingsAdmin());
    $settings = app(SettingsService::class);

    $this->post('/admin/settings/ai', aiTabPayload(['ai__enabled' => '0', 'review__auto_approve_min_score' => '0.85', 'ai__daily_limit' => '100']))->assertRedirect();
    expect($settings->bool(SettingKey::AiEnabled))->toBeFalse()->and($settings->float(SettingKey::ReviewAutoApproveMinScore))->toBe(0.85)->and($settings->get(SettingKey::AiDailyLimit))->toBe(100);
    $this->post('/admin/settings/ai', aiTabPayload(['ai__enabled' => '0', 'ai__daily_limit' => '']))->assertRedirect();
    expect($settings->get(SettingKey::AiDailyLimit))->toBeNull();

    $this->post('/admin/settings/search', ['search__driver' => 'like', 'seo__index_min_items' => '3', 'popularity__window_days' => '14', 'weights' => ['view' => '1', 'favorite' => '6', 'visited' => '2.5']])->assertRedirect();
    expect($settings->string(SettingKey::SearchDriver))->toBe('like')->and($settings->array(SettingKey::PopularityWeights))->toEqual(['view' => 1, 'favorite' => 6, 'visited' => 2.5]);
    $this->post('/admin/settings/search', ['search__driver' => 'like', 'seo__index_min_items' => '3', 'popularity__window_days' => '14', 'weights' => ['view' => '-1', 'favorite' => '6', 'visited' => '2']])->assertSessionHasErrors('popularity.weights');
    $this->post('/admin/settings/search', ['search__driver' => 'sphinx', 'seo__index_min_items' => '3', 'popularity__window_days' => '14', 'weights' => ['view' => '1', 'favorite' => '6', 'visited' => '2']])->assertSessionHasErrors('search.driver');
});

it('形のある設定(ホスト名・メール・Discord・AdSense・GA4)を検証する', function (): void {
    $this->actingAsVerifiedAdmin(settingsAdmin());

    $this->post('/admin/settings/site', ['site__name' => 'x', 'site__description' => '', 'site__operator' => '', 'site__share_host' => 'not a host'])->assertSessionHasErrors('site.share_host');
    $this->post('/admin/settings/mail', ['mail__from_address' => 'not-an-email', 'mail__smtp_host' => '', 'mail__smtp_port' => '587', 'mail__smtp_username' => ''])->assertSessionHasErrors('mail.from_address');
    $this->post('/admin/settings/mail', ['mail__from_address' => 'contact@do-inaka.net', 'mail__smtp_host' => 'smtp.example.com', 'mail__smtp_port' => '99999', 'mail__smtp_username' => ''])->assertSessionHasErrors('mail.smtp_port');
    $this->post('/admin/settings/notify', ['notify__discord_webhook_url' => 'https://evil.example/hook', 'crawl__max_pages_per_site' => '30', 'crawl__min_interval_seconds' => '10'])->assertSessionHasErrors('notify.discord_webhook_url');
    $this->post('/admin/settings/ads', ['ads__adsense_client_id' => 'pub-123', 'analytics__ga4_id' => 'G-ABCDEF1234'])->assertSessionHasErrors('ads.adsense_client_id');
    $this->post('/admin/settings/ads', ['ads__adsense_client_id' => 'ca-pub-1234567890123456', 'analytics__ga4_id' => 'UA-1'])->assertSessionHasErrors('analytics.ga4_id');

    $this->post('/admin/settings/ads', ['ads__enabled' => '1', 'ads__adsense_client_id' => 'ca-pub-1234567890123456', 'analytics__ga4_id' => 'G-ABCDEF1234'])->assertRedirect();
    $settings = app(SettingsService::class);
    expect($settings->bool(SettingKey::AdsEnabled))->toBeTrue()->and($settings->string(SettingKey::AnalyticsGa4Id))->toBe('G-ABCDEF1234');
});

it('Discord の Webhook URL は秘密の値として扱う(画面は末尾4文字、ログに残さない)', function (): void {
    $this->actingAsVerifiedAdmin(settingsAdmin());
    $url = 'https://discord.com/api/webhooks/123456789/AbCdEfGhIjKlMnOpQrStUvWxYz-secret1234';

    $this->post('/admin/settings/notify', ['notify__discord_webhook_url' => $url, 'crawl__max_pages_per_site' => '30', 'crawl__min_interval_seconds' => '10'])->assertRedirect();

    $html = $this->get('/admin/settings/notify')->getContent();
    expect($html)->toContain('********1234')->not->toContain('AbCdEfGhIjKl')
        ->and(json_encode(AuditLog::query()->get()->map->getAttributes()->all()))->not->toContain('AbCdEfGhIjKl');
});
