<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

function barEvent(): Event
{
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa']);
    $series = EventSeries::factory()->create(['region_id' => $pref->id, 'slug' => 'a']);

    return Event::factory()->published()->onDate(now()->addDays(3)->toDateString())->create(['series_id' => $series->id, 'region_id' => $pref->id, 'slug' => 'a']);
}

it('一般の人・会員・TOTP未確認の管理者には出ない', function (): void {
    $event = barEvent();
    $url = "/admin/bar?url=/kagawa/events/{$event->id}-a/";

    $this->get($url)->assertRedirect('/admin/login');
    $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
    // TOTP を設定していない管理者は設定画面へ、設定済みでも未確認ならコード入力へ
    $this->actingAs(User::factory()->admin()->create())->get($url)->assertRedirect();
});

it('確認済みの管理者には、そのページの操作つきで出る', function (): void {
    $event = barEvent();
    $admin = User::factory()->admin()->twoFactor()->create();

    $html = $this->actingAsVerifiedAdmin($admin)->get("/admin/bar?url=/kagawa/events/{$event->id}-a/")->assertOk()->getContent();

    expect($html)->toContain('管理者バー')->and($html)->toContain('中止にする')->and($html)->toContain('非公開にする')->and($html)->toContain('name="_token"');
});

it('公開ページのHTMLに管理者の情報は入らない(置き場の空の要素だけ)', function (): void {
    $event = barEvent();
    $admin = User::factory()->admin()->twoFactor()->create(['name' => '秘密の管理者名']);

    $html = $this->actingAsVerifiedAdmin($admin)->get("/kagawa/events/{$event->id}-a/")->assertOk()->getContent();
    expect($html)->toContain('id="admin-bar"')->and($html)->not->toContain('秘密の管理者名')->and($html)->not->toContain('中止にする');

});

it('一般の人には置き場も出ない', function (): void {
    $event = barEvent();

    $this->get("/kagawa/events/{$event->id}-a/")->assertOk()->assertDontSee('id="admin-bar"', false);
});

it('非公開の操作はPOSTで、CSRFトークンがなければ拒否される', function (): void {
    $event = barEvent();
    $admin = User::factory()->admin()->twoFactor()->create();

    $this->actingAsVerifiedAdmin($admin)->enforceCsrf()->post("/admin/bar/unpublish/event/{$event->id}")->assertStatus(419);
    expect($event->fresh()->is_published)->toBeTrue();
});

it('CSRFトークンつきなら非公開にでき、操作ログに残る。戻り先は同じサイトのパスだけ', function (): void {
    $event = barEvent();
    $admin = User::factory()->admin()->twoFactor()->create();

    $this->actingAsVerifiedAdmin($admin)->withoutMiddleware(PreventRequestForgery::class)
        ->post("/admin/bar/unpublish/event/{$event->id}", ['return' => 'https://evil.example/'])->assertRedirect('/');

    expect($event->fresh()->is_published)->toBeFalse()
        ->and(DB::table('audit_logs')->where('target_type', 'event')->where('target_id', $event->id)->exists())->toBeTrue();
});

it('管理者の閲覧はページビューに数えない', function (): void {
    $event = barEvent();
    $admin = User::factory()->admin()->twoFactor()->create();

    $this->actingAsVerifiedAdmin($admin)->get("/kagawa/events/{$event->id}-a/")->assertOk();

    expect(DB::table('page_views')->count())->toBe(0);
});

it('地域ページでは紹介文の再生成ができる', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa']);
    $admin = User::factory()->admin()->twoFactor()->create();

    $html = $this->actingAsVerifiedAdmin($admin)->get('/admin/bar?url=/kagawa/')->getContent();
    expect($html)->toContain('紹介文を再生成');

    $this->actingAsVerifiedAdmin($admin)->withoutMiddleware(PreventRequestForgery::class)
        ->post("/admin/bar/regions/{$pref->id}/regenerate")->assertRedirect();
    expect(DB::table('region_generation_queue')->where('region_id', $pref->id)->where('reason', 'admin')->count())->toBe(1);
});
