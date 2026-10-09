<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\SettingKey;
use App\Models\AdSlot;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\User;
use App\Services\Ads\AdSelector;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/../Submission/helpers.php';

function adEvent(): Event
{
    postWorld();
    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $series = EventSeries::factory()->create(['region_id' => $region->id]);

    return Event::factory()->published()->onDate(now()->addDay()->toDateString())->create(['series_id' => $series->id, 'region_id' => $region->id]);
}

function prSlot(array $override = []): AdSlot
{
    $slot = new AdSlot;
    $slot->forceFill(array_merge(['kind' => 'pr', 'position' => 'event_detail', 'title' => '丸亀の名産品フェア', 'body' => '期間限定です', 'link_url' => 'https://sponsor.example/fair', 'is_active' => true], $override))->save();

    return $slot;
}

beforeEach(function (): void {
    Storage::fake('local');
    Carbon::setTestNow(Carbon::parse('2026-10-12 12:00:00', 'Asia/Tokyo'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('PR枠は、開始前・終了後には表示されず、期間の中だけ「PR」つきで表示される', function (): void {
    $event = adEvent();
    $url = "/kagawa/events/{$event->id}/";
    prSlot(['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(3)]);

    // 開始前
    $this->get($url)->assertOk()->assertDontSee('丸亀の名産品フェア');

    // 期間の中: 「PR」と表示する(リンクは sponsored)
    Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00', 'Asia/Tokyo'));
    $html = $this->get($url)->assertOk()->assertSee('丸亀の名産品フェア')->getContent();
    expect($html)->toContain('ad-label">PR<')->and($html)->toContain('rel="sponsored nofollow noopener"');

    // 終了後は消える
    Carbon::setTestNow(Carbon::parse('2026-10-16 12:00:01', 'Asia/Tokyo'));
    $this->get($url)->assertOk()->assertDontSee('丸亀の名産品フェア');
});

it('無効にした PR 枠・別の場所の PR 枠は出ない', function (): void {
    $event = adEvent();
    prSlot(['is_active' => false]);
    prSlot(['position' => 'spot_detail', 'title' => 'スポットだけの広告']);

    $this->get("/kagawa/events/{$event->id}/")->assertOk()->assertDontSee('丸亀の名産品フェア')->assertDontSee('スポットだけの広告');
});

it('AdSense は、場所ごとの設定と全体の設定が揃ったときだけ出る(Google のスクリプトの読み込みは同意のあと)', function (): void {
    $event = adEvent();
    $settings = app(SettingsService::class);
    $row = new AdSlot;
    $row->forceFill(['kind' => 'adsense', 'position' => 'event_detail', 'is_active' => true])->save();
    $url = "/kagawa/events/{$event->id}/";

    // 全体がオフ / クライアント ID なし → 出ない
    $this->get($url)->assertDontSee('adsbygoogle');
    $settings->set(SettingKey::AdsEnabled, true);
    $this->get($url)->assertDontSee('adsbygoogle');
    // 設定の検証をすり抜けて、形の違う ID が入っていても、出さない(DB に直接入れる)
    DB::table('settings')->updateOrInsert(['key' => 'ads.adsense_client_id'], ['value' => json_encode('invalid-id'), 'is_secret' => false]);
    $settings->flush();
    $this->get($url)->assertDontSee('adsbygoogle');

    $settings->set(SettingKey::AdsAdsenseClientId, 'ca-pub-1234567890123456');
    $html = $this->get($url)->assertOk()->getContent();
    expect($html)->toContain('adsbygoogle')->and($html)->toContain('data-consent-ads')->and($html)->toContain('ca-pub-1234567890123456');

    // 場所の設定をオフにすると出ない
    $row->forceFill(['is_active' => false])->save();
    $this->get($url)->assertDontSee('adsbygoogle');
});

it('AdSense は、地図・投稿・マイページには、設定があっても出ない', function (): void {
    adEvent();
    $settings = app(SettingsService::class);
    $settings->set(SettingKey::AdsEnabled, true);
    $settings->set(SettingKey::AdsAdsenseClientId, 'ca-pub-1234567890123456');
    foreach (['map', 'post', 'mypage', 'top', 'event_detail'] as $position) {
        $row = new AdSlot;
        $row->forceFill(['kind' => 'adsense', 'position' => $position, 'is_active' => true])->save();
        $pr = new AdSlot;
        $pr->forceFill(['kind' => 'pr', 'position' => $position, 'title' => "PR-{$position}", 'link_url' => 'https://x.example', 'is_active' => true])->save();
    }

    $selector = app(AdSelector::class);
    expect($selector->adsense('map'))->toBeNull()->and($selector->adsense('post'))->toBeNull()->and($selector->adsense('mypage'))->toBeNull()
        ->and($selector->pr('map'))->toBeNull()->and($selector->pr('mypage'))->toBeNull()
        ->and($selector->adsense('event_detail'))->toBe('ca-pub-1234567890123456');

    $user = User::factory()->create();
    foreach (['/kagawa/map/', '/post/', '/post/spot/', '/post/tip/'] as $path) {
        $this->get($path)->assertOk()->assertDontSee('adsbygoogle')->assertDontSee('ad-pr');
    }
    $this->actingAs($user)->get('/mypage/')->assertOk()->assertDontSee('adsbygoogle')->assertDontSee('ad-pr');
    foreach (['/mypage/lists/', '/mypage/submissions/', '/mypage/profile/'] as $path) {
        $this->get($path)->assertOk()->assertDontSee('adsbygoogle');
    }
});

it('広告枠の管理画面: PR枠を期間つきで作る。終了が開始より前・http でないリンクは断る。操作ログに残る', function (): void {
    $admin = User::factory()->admin()->twoFactor()->create();
    $this->get('/admin/ads')->assertRedirect('/admin/login');
    $this->actingAsVerifiedAdmin(User::factory()->twoFactor()->create(['role' => 'editor']))->get('/admin/ads')->assertForbidden();

    $this->actingAsVerifiedAdmin($admin);
    $this->get('/admin/ads')->assertOk()->assertSee('広告枠');

    $payload = ['title' => '秋のフェア', 'body' => '説明', 'link_url' => 'https://sponsor.example/a', 'position' => 'top', 'starts_at' => '2026-10-20T09:00', 'ends_at' => '2026-10-25T18:00', 'is_active' => 1];
    $this->post('/admin/ads', array_merge($payload, ['ends_at' => '2026-10-19T09:00']))->assertSessionHasErrors('ends_at');
    $this->post('/admin/ads', array_merge($payload, ['link_url' => 'javascript:alert(1)']))->assertSessionHasErrors('link_url');
    $this->post('/admin/ads', array_merge($payload, ['position' => 'map']))->assertSessionHasErrors('position');

    $this->post('/admin/ads', $payload)->assertRedirect(route('admin.ads'));
    $slot = AdSlot::query()->where('kind', 'pr')->firstOrFail();
    expect($slot->starts_at?->setTimezone('Asia/Tokyo')->format('Y-m-d H:i'))->toBe('2026-10-20 09:00')->and($slot->is_active)->toBeTrue();

    $this->put("/admin/ads/{$slot->id}", array_merge($payload, ['title' => '秋のフェア(更新)']))->assertRedirect();
    $this->post('/admin/ads/adsense', ['positions' => ['top', 'list', 'map']])->assertRedirect();
    expect(AdSlot::query()->where('kind', 'adsense')->where('is_active', true)->pluck('position')->sort()->values()->all())->toBe(['list', 'top']);
    $this->delete("/admin/ads/{$slot->id}")->assertRedirect(route('admin.ads'));
    expect(AdSlot::query()->where('kind', 'pr')->count())->toBe(0)->and(AuditLog::query()->where('action', AuditAction::AdChange->value)->count())->toBe(4);
});
