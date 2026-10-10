<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    // フェーズ3以降に作る投稿・コメント・反応のルートと同じ形(can: で権限を絞る)の試験用ルート
    Route::middleware('web')->group(function (): void {
        Route::get('/__view', fn () => 'ok')->middleware('can:view');
        Route::post('/__post', fn () => 'ok')->middleware('can:post');
        Route::post('/__visit', fn () => 'ok')->middleware('can:visit');
        Route::post('/__comment', fn () => 'ok')->middleware('can:comment');
        Route::post('/__favorite', fn () => 'ok')->middleware('can:favorite');
        Route::get('/__my-page', fn () => 'ok')->middleware('can:my-page');
    });
});

it('停止中の会員は、閲覧・マイページはできるが、投稿・コメント・反応は 403 になる', function (): void {
    $suspended = User::factory()->suspended()->create();

    $this->actingAs($suspended)->get('/__view')->assertOk();
    $this->actingAs($suspended)->get('/__my-page')->assertOk();

    foreach (['/__post', '/__comment', '/__visit', '/__favorite'] as $url) {
        $this->actingAs($suspended)->post($url)->assertForbidden();
    }
});

it('会員は、すべてできる', function (): void {
    $member = User::factory()->create();

    $this->actingAs($member)->get('/__view')->assertOk();
    $this->actingAs($member)->get('/__my-page')->assertOk();
    foreach (['/__post', '/__comment', '/__visit', '/__favorite'] as $url) {
        $this->actingAs($member)->post($url)->assertOk();
    }
});

it('ログインしていない閲覧者は、閲覧・投稿・「行った!」はできるが、コメント・お気に入り・マイページはできない', function (): void {
    $this->get('/__view')->assertOk();
    $this->post('/__post')->assertOk();
    $this->post('/__visit')->assertOk();

    $this->post('/__comment')->assertForbidden();
    $this->post('/__favorite')->assertForbidden();
    $this->get('/__my-page')->assertForbidden();
});

it('停止から戻した会員は、また投稿できる', function (): void {
    $user = User::factory()->suspended()->create();
    $this->actingAs($user)->post('/__post')->assertForbidden();

    $user->forceFill(['status' => 'active'])->save();

    $this->actingAs($user->refresh())->post('/__post')->assertOk();
});
