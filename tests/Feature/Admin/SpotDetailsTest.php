<?php

declare(strict_types=1);

use App\Models\Region;
use App\Models\Spot;
use App\Models\User;
use App\Services\Url\PublicLinks;

/*
 * スポットの基本情報の項目(休み・料金・駐車場・支払い・トイレ・問い合わせ先・季節・注意): 管理画面で入れ、公開の詳細に出る。
 */
it('管理画面でスポットの基本情報を入れると、公開の詳細に出る', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'marugame', 'name' => '丸亀市']);
    $admin = User::factory()->admin()->twoFactor()->create();

    $this->actingAsVerifiedAdmin($admin)->post('/admin/spots', [
        'title' => '峠の無人販売所', 'region_id' => $city->id, 'state' => 'published',
        'hours' => '日の出〜日の入り', 'closed_days' => '不定休', 'fee' => '無料', 'parking' => '路肩に1台', 'payment' => '現金のみ', 'toilet' => 'なし',
        'contact' => '町の産業課', 'best_season' => 'autumn', 'caution_text' => '小銭の用意をお忘れなく。', 'private_land_checked' => '1',
    ])->assertRedirect();

    $spot = Spot::query()->firstOrFail();
    expect($spot->closed_days)->toBe('不定休')->and($spot->best_season)->toBe('autumn')->and($spot->private_land_checked)->toBeTrue();

    auth()->logout();
    $this->flushSession();
    $this->get(app(PublicLinks::class)->spot($spot))->assertOk()->assertSee('お休み')->assertSee('不定休')->assertSee('路肩に1台')->assertSee('現金のみ')->assertSee('町の産業課')->assertSee('小銭の用意をお忘れなく。')->assertSee('いちばんの季節: 秋');
});

it('いちばんの季節に変な値を入れると断る', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'marugame']);
    $this->actingAsVerifiedAdmin(User::factory()->admin()->twoFactor()->create())->post('/admin/spots', ['title' => 'x', 'region_id' => $city->id, 'state' => 'draft', 'best_season' => 'rainy'])->assertSessionHasErrors('best_season');
});
